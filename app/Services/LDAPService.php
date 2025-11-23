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
        $attrs  = ['dn', 'cn', $this->uidAttribute, $this->mailAttribute];
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
}
