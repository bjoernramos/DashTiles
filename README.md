# toolpages

toolpages is a lightweight, self-hosted dashboard where each user can create and customize their own page of tool shortcuts. Supports categories, icons, and personal layouts. Designed for simple, fast access to internal web tools without shared global config.

Roadmap (current session)
1) Scaffold CodeIgniter 4.6.3 project (PHP 8.3)
2) Add Docker (PHP-FPM + Nginx + MariaDB) and .env defaults
3) Users migration + model
4) Auth (local + LDAP), filters, views
5) Admin user management
6) Basic API groundwork for future tool tiles
7) Feature tests and docs

Quickstart (Docker)
- Requirements: Docker, Docker Compose
1) cp .env.example .env
2) docker compose up -d --build
3) docker compose exec app composer install
4) docker compose exec app php spark key:generate
5) docker compose exec app php spark migrate
6) docker compose exec app php spark db:seed AdminSeeder
7) Open http://localhost:8080/

Default admin (first run)
- username: admin
- password: admin123

Testing
- Run vendor/bin/phpunit or docker compose exec app composer test

Reverse proxy / base path
- By default the app is served at root (/). If you host behind a reverse proxy under a subpath (e.g., /toolpages), set in your .env:
  - app.baseURL = 'https://your-host/toolpages/'
  - toolpages.basePath = '/toolpages'
  Then access the app at the configured subpath.

LDAP group restriction (optional)
- You can restrict LDAP logins to members of a specific group via .env. Two options are supported:
  1) Fixed Group DN (recommended):
     - ldap.groupDN = 'cn=toolpages,ou=applications,ou=groups,dc=b-ramos,dc=de'
     - The app reads the group entry and checks membership via member/uniqueMember (DN-based) and memberUid (uid-based for posixGroup).
  2) Dynamic filter with placeholders:
     - ldap.groupFilter = '(memberOf=cn=toolpages,ou=groups,dc=example,dc=org)'
     - Supported placeholders: {dn}, {uid}
       - Example (groupOfNames): '(&(objectClass=groupOfNames)(cn=toolpages)(member={dn}))'
       - Example (posixGroup with memberUid): '(&(objectClass=posixGroup)(cn=toolpages)(memberUid={uid}))'
  - Precedence: If ldap.groupDN is set, it will be used and ldap.groupFilter will be ignored.
  - Leave both empty to allow all authenticated LDAP users.
