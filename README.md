# toolpages

toolpages is a lightweight, self-hosted dashboard where each user can create and customize their own page of tool shortcuts. Supports categories, icons, and personal layouts. Designed for simple, fast access to internal web tools without shared global config.

Roadmap (current session)
1) Scaffold CodeIgniter 4.6.3 project (PHP 8.3)
2) Add Docker (PHP-FPM + Nginx + MariaDB) and .env defaults
3) Users migration + model
4) Auth (local + LDAP), filters, views
5) Admin user management
6) Per-user Dashboard Tiles (links, iframes, files) with layout & categories
7) Admin-defined global tiles (visible for all users)
8) Visibility per tile: assign to specific users and/or groups; groups are admin-managed
9) Feature tests and docs
10) Bootstrap 5 Styles via CDN (basic UI styling)

Quickstart (Docker)
- Requirements: Docker, Docker Compose
1) cp .env.example .env
2) docker compose up -d --build
3) docker compose exec app composer install
4) docker compose exec app php spark key:generate
5) docker compose exec app php spark migrate
6) docker compose exec app php spark db:seed AdminSeeder
7) Open http://localhost:8080/

UI/Styles
- Bootstrap 5 is included via CDN on all main pages (Startseite, Login, Dashboard, Admin-Users).
- To self-host Bootstrap instead of using CDN, replace the partials:
  - app/Views/partials/bootstrap_head.php
  - app/Views/partials/bootstrap_scripts.php
  with local asset references (e.g., /public/vendor/bootstrap/*.css and *.js) and adjust your build/deploy accordingly.

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
  
Per-user Dashboard Tiles
- Nach dem Login werden dem Nutzer seine Kacheln direkt auf der Startseite (/) angezeigt.
- Verwaltung/Anlegen/Bearbeiten erfolgt unter /dashboard.
- Tile types supported:
  - link: A simple link (can display an icon class or image URL and optional text label)
  - iframe: Embeds an external page via iframe (be mindful of X-Frame-Options of the target)
  - file: Upload a file stored under writable/uploads/{user_id}/ and served securely via /file/{tileId}
- Categories: Tiles can be assigned a category; the dashboard renders rows per category.
- Layout: Users can choose 1–6 columns; stored per user.
- Global tiles: Admins can mark tiles as "global" so they are displayed for all users in addition to their personal tiles. Global file tiles are downloadable by any logged-in user.

Schema (migrations included)
- tiles: id, user_id, is_global (tinyint, default 0), type(enum: link, iframe, file), title, url, icon, text, category, position, timestamps, soft-deletes
- user_settings: user_id (PK), columns, timestamps
- groups: id, name, timestamps
- user_groups: user_id, group_id (pivot)
- tile_users: tile_id, user_id (pivot)
- tile_groups: tile_id, group_id (pivot)

How to enable
1) Run migrations after pulling changes:
   docker compose exec app php spark migrate
2) Log in and open http://localhost:8080/ — deine Kacheln erscheinen auf der Startseite.
3) Öffne http://localhost:8080/dashboard um Spaltenzahl zu konfigurieren und Kacheln (Link, Iframe, Datei) anzulegen/zu bearbeiten und nach Kategorien zu gruppieren.
4) Sichtbarkeit einer Kachel:
   - Global: Als Admin Checkbox „Global (für alle Nutzer anzeigen)“ setzen.
   - Benutzer: Eine oder mehrere Personen auswählen.
   - Gruppen: Eine oder mehrere Gruppen auswählen (vorher unter Admin → Gruppen anlegen und mit Benutzern befüllen).
   - Hinweis: Global wirkt zusätzlich. Ohne Auswahl sehen standardmäßig der Besitzer (Ersteller) die Kachel, plus alle explizit zugewiesenen Benutzer/Gruppen.

Admin → Gruppen
- Admins können Gruppen anlegen (Name) und Mitglieder über eine Mehrfachauswahl verwalten.
- Benutzer können in mehreren Gruppen sein.
- Kacheln können einer oder mehreren Gruppen und/oder Benutzern zugewiesen werden.

Security notes
- File tiles are served only to the owning user via a controller that verifies ownership. For global file tiles, download is allowed for any logged-in user.
- Uploaded files are stored under writable/uploads/{user_id}/ and are not web-accessible directly via Nginx.
- Only admins can mark tiles as global or delete global tiles; normal users cannot modify global tiles.
 - Tile visibility checks include: owner, global, explicit user assignment, membership via assigned groups.
