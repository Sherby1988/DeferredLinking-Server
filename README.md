# Deferred Linking — Server

Laravel 12 backend for the Deferred Linking platform. A self-hosted alternative to Firebase Dynamic Links that manages short links, deferred deep links, and click analytics for React Native apps.

Pair this with the [React Native SDK](https://github.com/Sherby1988/DeferredLinking-RN-SDK) to send users to the right screen inside your app even when they install it *after* clicking a short link.

---

## Requirements

- PHP 8.4
- Composer
- SQLite (included with PHP — zero extra setup)

---

## Setup

### 1. Install dependencies

```bash
cd backend
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
DB_CONNECTION=sqlite

# Secret for creating and managing app records — never expose this publicly
DEFERRED_LINKING_ADMIN_KEY=your-very-secret-admin-key

# The domain this server is reachable on (used to build short URLs)
DEFERRED_LINKING_DEFAULT_DOMAIN=links.yourapp.com

# How long a deferred link stays matchable after the user clicks (default: 24h)
DEFERRED_LINKING_TTL_HOURS=24
```

### 3. Create the database and run migrations

```bash
touch database/database.sqlite
php artisan migrate
```

### 4. Start the development server

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

---

## Register your first app

Each mobile app gets its own isolated record with a unique API key.

### Via Artisan command (recommended)

```bash
# Interactive — prompts for each field
php artisan deferred-linking:create-app

# Non-interactive — pass all fields as flags
php artisan deferred-linking:create-app \
  --name="My App" \
  --ios="com.example.myapp" \
  --android="com.example.myapp" \
  --app-store="https://apps.apple.com/app/my-app/id123456789" \
  --play-store="https://play.google.com/store/apps/details?id=com.example.myapp" \
  --scheme="myapp://" \
  --domain="links.myapp.com"
```

Output:

```
App created successfully.

+----------------+--------------------+
| Field          | Value              |
+----------------+--------------------+
| ID             | 1                  |
| Name           | My App             |
| iOS bundle     | com.example.myapp  |
| Android bundle | com.example.myapp  |
| URI scheme     | myapp://           |
| Custom domain  | links.myapp.com    |
+----------------+--------------------+

  API Key (store this — it will not be shown again):

    a3f8c2d1e5b9...
```

### Via REST API

```bash
curl -X POST https://links.yourapp.com/api/apps \
  -H "X-Admin-Key: your-very-secret-admin-key" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My App",
    "bundle_id_ios": "com.example.myapp",
    "bundle_id_android": "com.example.myapp",
    "app_store_url": "https://apps.apple.com/app/my-app/id123456789",
    "play_store_url": "https://play.google.com/store/apps/details?id=com.example.myapp",
    "uri_scheme": "myapp://",
    "custom_domain": "links.myapp.com"
  }'
```

---

## API overview

### Authentication

| Header | Used for |
|---|---|
| `X-Admin-Key` | App management (`/api/apps/*`) |
| `X-Api-Key` | All other `/api/*` routes |

### Endpoints

#### App management — `X-Admin-Key`

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/apps` | Create app, returns `api_key` |
| `GET` | `/api/apps/{id}` | Get app details |
| `PUT` | `/api/apps/{id}` | Update app fields |
| `DELETE` | `/api/apps/{id}` | Delete app and all its links |

#### Links — `X-Api-Key`

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/links` | Create a short link |
| `GET` | `/api/links` | List links (paginated, `?page=n`) |
| `GET` | `/api/links/{code}` | Get link detail + short URL |
| `DELETE` | `/api/links/{code}` | Delete a link |

#### Deferred linking — `X-Api-Key`

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/deferred/resolve` | SDK cold-start resolution |
| `POST` | `/api/internal/fingerprint-update` | Beacon from redirect page (no auth) |

#### Analytics — `X-Api-Key`

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/links/{code}/analytics` | Per-link click stats |
| `GET` | `/api/analytics/summary` | App-wide stats |

#### Public

| Method | Path | Description |
|---|---|---|
| `GET` | `/{shortCode}` | Redirect page — opens app or stores |

---

## How deferred linking works

```
User clicks https://links.myapp.com/xK9pQr
        │
        ▼
Backend records click, stores DeferredLink row (fingerprint = SHA256 of ip+ua+lang+screen)
        │
        ▼
Redirect page JS tries to open URI scheme (myapp://...)
   ├── App installed → opens directly ✓
   └── Not installed → sendBeacon refines fingerprint with screen dims
                     → after 2.5s redirect to App Store / Play Store
                               │
                               ▼
                     User installs and opens app
                               │
                               ▼
                     SDK: POST /api/deferred/resolve
                               │
                               ▼
               Two-pass fingerprint match:
               Pass 1 (exact)  SHA256(ip + ua + lang + bucketed_screen)  — 24h window
               Pass 2 (loose)  SHA256(ip + ua) only                      — 1h window
                               │
                               ▼
               matched: true → deep_link_uri returned to SDK
```

### Fingerprint algorithm

Both the redirect side and the resolve side use the identical function so hashes match:

```
SHA256(
  lowercase(trim(ip))          + "|" +
  lowercase(trim(user_agent))  + "|" +
  lowercase(trim(language))    + "|" +
  round(screen_width  / 10) * 10   + "|" +   ← bucketed to ±5px
  round(screen_height / 10) * 10
)
```

---

## Database schema

| Table | Description |
|---|---|
| `apps` | One row per registered mobile app |
| `links` | Short links belonging to an app |
| `link_clicks` | One row per redirect page visit |
| `deferred_links` | Pending deferred matches, expires after TTL |

### `apps`

| Column | Type | Notes |
|---|---|---|
| `id` | PK | |
| `name` | string | |
| `api_key` | string(64) unique | `bin2hex(random_bytes(32))` |
| `bundle_id_ios` | string | e.g. `com.example.app` |
| `bundle_id_android` | string | |
| `app_store_url` | string | |
| `play_store_url` | string | |
| `custom_domain` | string nullable unique | e.g. `links.myapp.com` |
| `uri_scheme` | string | e.g. `myapp://` |

### `links`

| Column | Type | Notes |
|---|---|---|
| `id` | PK | |
| `app_id` | FK → apps | cascade delete |
| `short_code` | string(16) unique | 6-char alphanumeric |
| `deep_link_uri` | string | e.g. `myapp://products/42` |
| `fallback_url` | string nullable | |
| `og_title`, `og_description`, `og_image_url` | nullable | OpenGraph metadata |
| `expires_at` | timestamp nullable | |

### `link_clicks`

| Column | Type | Notes |
|---|---|---|
| `id` | PK | |
| `link_id` | FK → links | cascade delete |
| `app_id` | FK → apps | |
| `ip_address` | string(45) | |
| `user_agent` | text | |
| `platform` | string(16) | `ios\|android\|web\|unknown` |
| `country`, `referer` | nullable | |
| `clicked_at` | timestamp | auto-set on insert |

### `deferred_links`

| Column | Type | Notes |
|---|---|---|
| `id` | PK | |
| `link_id` | FK → links | cascade delete |
| `app_id` | FK → apps | |
| `fingerprint` | string(64) | SHA-256 hash |
| `platform` | string(16) | `ios\|android` |
| `resolved` | boolean | default false |
| `resolved_at` | timestamp nullable | |
| `expires_at` | timestamp | `created_at + TTL` |
| `created_at` | timestamp | auto-set on insert |

---

## Artisan commands

### `deferred-linking:create-app`

Register a new app. Prints the API key once — not retrievable again.

```bash
php artisan deferred-linking:create-app [--name=] [--ios=] [--android=]
  [--app-store=] [--play-store=] [--scheme=] [--domain=]
```

### `deferred-linking:prune`

Remove expired and already-resolved rows from `deferred_links`. Runs hourly via the scheduler.

```bash
php artisan deferred-linking:prune          # delete
php artisan deferred-linking:prune --dry-run  # preview count only
```

---

## Scheduler

The pruning job runs hourly automatically. Enable it with one cron entry:

```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## Running tests

```bash
php artisan test
```

20 Pest tests covering:

| Suite | Tests |
|---|---|
| `AppApiTest` | Admin auth, create/get/delete app |
| `LinkApiTest` | Create/list/get/delete, cross-app isolation |
| `DeferredLinkTest` | Exact match, no match, resolved flag |
| `RedirectTest` | Page loads, 404 on unknown/expired, click + deferred row creation |
| `ExampleTest` | Health endpoint |

---

## Project structure

```
app/
├── Console/Commands/
│   ├── CreateApp.php               deferred-linking:create-app
│   └── PruneExpiredDeferredLinks.php  deferred-linking:prune
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AppController.php
│   │   │   ├── LinkController.php
│   │   │   ├── AnalyticsController.php
│   │   │   └── DeferredLinkController.php
│   │   └── Web/
│   │       └── RedirectController.php
│   ├── Middleware/
│   │   ├── AuthenticateApiKey.php
│   │   ├── AuthenticateAdminKey.php
│   │   └── ResolveAppByDomain.php
│   └── Requests/
│       ├── CreateLinkRequest.php
│       └── ResolveDeferredRequest.php
├── Models/
│   ├── App.php
│   ├── Link.php
│   ├── LinkClick.php
│   └── DeferredLink.php
└── Services/
    ├── FingerprintService.php
    ├── ShortCodeService.php
    └── DeviceDetectionService.php
config/
└── deferred_linking.php
database/migrations/           4 custom migrations
resources/views/redirect/
└── index.blade.php            redirect page with JS deep-link logic
routes/
├── api.php
└── web.php
```

---

## Production deployment

Point Nginx or Caddy at `public/index.php`. Every `custom_domain` you register on an app must DNS-resolve to this server.

Nginx example:

```nginx
server {
    listen 80;
    server_name ~^(.+)$;

    root /var/www/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

The `server_name ~^(.+)$` catch-all ensures all custom domains are routed to Laravel regardless of hostname. Laravel's `ResolveAppByDomain` middleware then looks up the matching app record.

---

## License

MIT
