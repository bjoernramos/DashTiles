<?php

namespace App\Services;

class LDAPService
{
    private ?\LDAP\Connection $conn = null;

    private string $host;
    private int $port;
    private string $encryption; // none|ssl|tls
    private string $baseDN;
    private string $adminDN;
    private string $adminPassword;
    private string $uidAttribute;
    private string $mailAttribute;
    private string $groupFilter; // optional LDAP filter to enforce group membership
    private string $groupDN;     // optional Group DN to enforce membership by DN

    public function __construct()
    {
        $this->host          = (string) (getenv('ldap.host') ?: 'ldap');
        $this->port          = (int) (getenv('ldap.port') ?: 389);
        $this->encryption    = (string) (getenv('ldap.encryption') ?: 'none');
        $this->baseDN        = (string) (getenv('ldap.baseDN') ?: '');
        $this->adminDN       = (string) (getenv('ldap.adminDN') ?: '');
        $this->adminPassword = (string) (getenv('ldap.adminPassword') ?: '');
        $this->uidAttribute  = (string) (getenv('ldap.uidAttribute') ?: 'uid');
        $this->mailAttribute = (string) (getenv('ldap.mailAttribute') ?: 'mail');
        // Example (memberOf style): (memberOf=cn=toolpages,ou=groups,dc=example,dc=org)
        // Example (groupOfNames style): (&(objectClass=groupOfNames)(cn=toolpages)(member={dn}))
        $this->groupFilter   = (string) (getenv('ldap.groupFilter') ?: '');
        // Optional fixed group DN (e.g., cn=toolpages,ou=applications,ou=groups,dc=b-ramos,dc=de)
        $this->groupDN       = (string) (getenv('ldap.groupDN') ?: '');
    }

    private function connect(): void
    {
        if ($this->conn) {
            return;
        }
        $host = $this->host;
        if ($this->encryption === 'ssl') {
            $host = 'ldaps://' . $host;
            if ($this->port === 389) {
                $this->port = 636;
            }
        }
        $this->conn = ldap_connect($host, $this->port);
        if (! $this->conn) {
            throw new \RuntimeException('LDAP connect failed');
        }
        ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->conn, LDAP_OPT_REFERRALS, 0);
    }

    private function maybeStartTLS(): void
    {
        if ($this->encryption === 'tls') {
            if (! ldap_start_tls($this->conn)) {
                throw new \RuntimeException('Failed to start TLS on LDAP connection');
            }
        }
    }

    public function adminBind(): void
    {
        $this->connect();
        $this->maybeStartTLS();
        if ($this->adminDN === '') {
            // anonymous bind
            @ldap_bind($this->conn);
            return;
        }
        if (! @ldap_bind($this->conn, $this->adminDN, $this->adminPassword)) {
            throw new \RuntimeException('LDAP admin bind failed');
        }
    }

    /**
     * @return array{dn:string, cn:?string, uid:string, mail:?string}|null
     */
    public function findUserByUid(string $uid): ?array
    {
        $this->adminBind();
        $filter = sprintf('(%s=%s)', $this->uidAttribute, ldap_escape($uid, '', LDAP_ESCAPE_FILTER));
        $attrs  = ['dn', 'cn', $this->uidAttribute, $this->mailAttribute, 'memberOf'];
        $search = @ldap_search($this->conn, $this->baseDN, $filter, $attrs, 0, 1);
        if (! $search) {
            return null;
        }
        $entries = ldap_get_entries($this->conn, $search);
        if ($entries['count'] < 1) {
            return null;
        }
        $entry = $entries[0];
        return [
            'dn'   => $entry['dn'] ?? '',
            'cn'   => isset($entry['cn'][0]) ? (string) $entry['cn'][0] : null,
            'uid'  => isset($entry[$this->uidAttribute][0]) ? (string) $entry[$this->uidAttribute][0] : $uid,
            'mail' => isset($entry[$this->mailAttribute][0]) ? (string) $entry[$this->mailAttribute][0] : null,
        ];
    }

    public function authenticate(string $userDN, string $password): bool
    {
        $this->connect();
        $this->maybeStartTLS();
        return @ldap_bind($this->conn, $userDN, $password) === true;
    }

    /**
     * Checks whether the given LDAP entry satisfies the configured group filter.
     * If no groupFilter is configured, returns true.
     *
     * Supported placeholders inside ldap.groupFilter:
     *  - {dn}: full distinguished name of the user
     *  - {uid}: value of configured uidAttribute for the user
     */
    public function isMemberOfRequiredGroup(array $entry): bool
    {
        // No restriction configured
        if ($this->groupDN === '' && $this->groupFilter === '') {
            return true;
        }

        $this->adminBind();

        $dn  = (string) ($entry['dn'] ?? '');
        $uid = (string) ($entry['uid'] ?? '');

        // 1) Preferred: fixed group DN membership check
        if ($this->groupDN !== '') {
            // Read the group entry (BASE scope) and inspect common member attributes
            $groupRead = @ldap_read($this->conn, $this->groupDN, '(objectClass=*)', ['member', 'uniqueMember', 'memberUid']);
            if (! $groupRead) {
                return false;
            }
            $groups = @ldap_get_entries($this->conn, $groupRead);
            if (! is_array($groups) || ($groups['count'] ?? 0) < 1) {
                return false;
            }
            $g = $groups[0];

            // Helper to check multi-valued attributes for a value
            $hasValue = static function ($attr, string $value) use ($g): bool {
                $attr = strtolower($attr);
                if (! isset($g[$attr]['count'])) {
                    return false;
                }
                $count = (int) $g[$attr]['count'];
                for ($i = 0; $i < $count; $i++) {
                    if (isset($g[$attr][$i]) && strcasecmp((string) $g[$attr][$i], $value) === 0) {
                        return true;
                    }
                }
                return false;
            };

            // Compare DN-based memberships
            if ($dn !== '' && ($hasValue('member', $dn) || $hasValue('uniquemember', $dn))) {
                return true;
            }
            // Compare uid membership for posixGroup
            if ($uid !== '' && $hasValue('memberuid', $uid)) {
                return true;
            }
            return false;
        }

        // 2) Fallback: dynamic filter with placeholders
        $dnEscaped  = ldap_escape($dn, '', LDAP_ESCAPE_FILTER);
        $uidEscaped = ldap_escape($uid, '', LDAP_ESCAPE_FILTER);
        $filter = str_replace(['{dn}', '{uid}'], [$dnEscaped, $uidEscaped], $this->groupFilter);

        $search = @ldap_search($this->conn, $this->baseDN, $filter, ['dn'], 0, 1);
        if (! $search) {
            return false;
        }
        $entries = @ldap_get_entries($this->conn, $search);
        return is_array($entries) && isset($entries['count']) && (int) $entries['count'] >= 1;
    }
}
