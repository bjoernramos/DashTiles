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
