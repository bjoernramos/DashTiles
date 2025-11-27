# DashTiles

DashTiles is a lightweight, self-hosted dashboard where each user can create and customize their own page of tool shortcuts. Supports categories, icons, and personal layouts. Designed for simple, fast access to internal web tools without shared global config.

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
10) Bootstrap 5 Styles lokal eingebunden

Local Icon Sets (line-md + Material Design Icons)
- Icon‑Sets werden lokal selbst gehostet und über npm bereitgestellt. Es gibt keine externen CDN‑Abhängigkeiten mehr. Die Assets werden per postinstall in public/assets/vendor/ synchronisiert oder direkt aus /node_modules referenziert.

Included integrations
- Material Symbols (Outlined): lokale CSS unter public/assets/vendor/material-symbols/material-symbols.css (Fonts aus npm).
- Material Icons (legacy baseline): lokale CSS unter public/assets/vendor/material-icons/material-icons.css (Fonts aus npm).
- line-md icon set: via Iconify Runtime lokal. Die App lädt die lokale Runtime und die line‑md JSON aus public/assets/vendor oder ersatzweise aus /node_modules. Keine CDN‑Requests.

NPM workflow (preferred)
1) Install Node.js 18+ on your workstation or run this in CI.
2) At the repo root, run:
   - npm i
   This will install the required npm packages and automatically copy the needed runtime/font files into public/assets/vendor via a postinstall script.
3) You can re-run the copy step at any time with:
   - npm run icons:sync
4) What gets synced:
   - Iconify runtime → public/assets/vendor/iconify/iconify.min.js
   - line-md icons.json → public/assets/vendor/iconify/line-md.json
   - Material Symbols WOFF2 → public/assets/vendor/material-symbols/fonts/material-symbols-outlined.woff2
   - Material Icons WOFF2 → public/assets/vendor/material-icons/fonts/MaterialIcons-Regular.woff2
5) Wenn eine Datei fehlt, greift die App auf die lokale /node_modules‑Quelle zurück. Es werden keine externen CDNs verwendet.

Usage in tiles (icon field)
- line-md via Iconify: set icon to e.g. "line-md:home" → renders <span class="iconify" data-icon="line-md:home"></span>
- Material Icons (legacy baseline): set icon to e.g. "mi:home" → renders <span class="material-icons">home</span>
- Material Symbols (outlined): set icon to e.g. "ms:home" → renders <span class="material-symbols-outlined">home</span>
- Image URL or class name still supported: if icon starts with http(s):// or / it is treated as an image URL; otherwise it is used as a CSS class name (for custom icon libraries).

Notes
- Es gibt keine CDN‑Fallbacks mehr. Stelle sicher, dass npm‑Abhängigkeiten installiert sind (npm i), damit alle Assets lokal vorhanden sind.
- Hochgeladene Icon-/Hintergrundbilder pro Kachel haben weiterhin Priorität vor dem Icon‑Feld.

Quickstart (Docker)
- Requirements: Docker, Docker Compose
1) cp .env.example .env
2) docker compose up -d --build
3) docker compose exec app composer install
4) docker compose exec app php spark key:generate
5) docker compose exec app php spark migrate
6) docker compose exec app php spark db:seed AdminSeeder
7) Open http://localhost:8080/

Notes for Docker build/runtime
- Der App-Container baut die Frontend-Assets jetzt vollständig im Dockerfile:
  - Während des Builds werden npm-Abhängigkeiten installiert und das postinstall-Skript kopiert alle benötigten Dateien nach public/assets/vendor/.
  - Ein Snapshot dieser erzeugten Assets wird im Image unter /opt/toolpages-assets/vendor abgelegt.
  - Ein Entrypoint-Skript kopiert beim Container-Start fehlende Dateien aus dem Snapshot in das (gemountete) public/assets/vendor/, ohne bestehende Dateien zu überschreiben. So sind die Assets auch bei Bind-Mounts zuverlässig vorhanden.
- Es gibt keinen separaten "assets"-Service mehr in docker-compose; die Images enthalten die Assets bereits. 404-Probleme bei CSS/JS/Fonts sollten damit nicht mehr auftreten.

Asset-Lade-Reihenfolge im Frontend
- Bootstrap CSS/JS und Iconify/Fonts werden primär aus public/assets/vendor geladen (vom Build erzeugt und zur Laufzeit gesichert).
- Fallback: Die Templates enthalten einen optionalen Fallback auf /node_modules, der im Normalfall nicht benötigt wird. Externe CDNs werden nicht verwendet.

NodeModules (optional)
- Die Anwendung ist nicht mehr darauf angewiesen, node_modules zur Laufzeit auszuliefern. Alle benötigten Dateien werden beim Image-Build in public/assets/vendor erzeugt und als Snapshot gespeichert.
- Der vorhandene /node_modules-Fallback in den Templates ist lediglich eine Schutzmaßnahme, falls du lokal ohne rebuild experimentierst. Für Produktion ist er nicht erforderlich.

UI/Styles
- Bootstrap 5 ist lokal eingebunden aus public/assets/vendor/bootstrap (vom Build erzeugt). Die Partials sind:
  - app/Views/partials/bootstrap_head.php
  - app/Views/partials/bootstrap_scripts.php
  Ein optionaler Fallback auf /node_modules ist vorhanden, sollte aber im regulären Betrieb nicht benötigt werden.

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

Plugins (MVP)
- Ab dieser Version existiert ein erstes, statisches Plugin‑Serving und eine Plugin‑Liste (Phase 1 des Plans):
  - Statische Routen (BASE_PATH wird berücksichtigt):
    - GET {BASE_PATH}/plugins/{pluginId}/plugin.json – liefert das Manifest eines Plugins
    - GET {BASE_PATH}/plugins/{pluginId}/web/* – liefert Plugin‑Web‑Assets (ES‑Module, CSS, Bilder)
    - GET {BASE_PATH}/api/plugins – listet installierte Plugins (FS‑Scan)
  - Ein Demo‑Plugin ist im Repo enthalten: plugins/demo/
    - Manifest: {BASE_PATH}/plugins/demo/plugin.json
    - Beispiel‑Modul: {BASE_PATH}/plugins/demo/web/index.js
  - Hinweise:
    - Für Production bitte CSP anpassen (siehe .env app.CSPEnabled) – Dynamic Imports werden später berücksichtigt.
    - Dies ist der erste Schritt. Die Frontend‑Registry und der Plugins‑Tab im #addTileModal folgen in den nächsten Phasen.
