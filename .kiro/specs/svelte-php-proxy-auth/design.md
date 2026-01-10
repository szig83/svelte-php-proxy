# Tervezési Dokumentum

## Áttekintés

Ez a dokumentum egy Svelte 5 + PHP proxy alapú webalkalmazás architektúráját írja le, amely biztonságosan kommunikál egy külső API rendszerrel. A rendszer SSG (Static Site Generation) módban épül, így Apache + PHP környezetben telepíthető JavaScript runtime nélkül.

### Fő tervezési döntések

1. **Svelte 5 SSG mód**: A SvelteKit `adapter-static` használata statikus fájlok generálásához
2. **PHP Proxy Backend**: Minden API kommunikáció a PHP proxy-n keresztül történik, amely kezeli a tokeneket és titkos adatokat
3. **Session-alapú token tárolás**: A JWT tokenek a PHP session-ben tárolódnak, soha nem kerülnek a kliensre
4. **Hierarchikus route védelem**: Layout-alapú védelem, ahol egy layout védetté tétele az összes gyermek útvonalat is védi
5. **Jogosultság-alapú UI**: A felhasználói jogosultságok alapján feltételes UI megjelenítés

## Architektúra

```
┌─────────────────────────────────────────────────────────────────┐
│                         Böngésző                                │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              Svelte 5 Frontend (SSG)                    │    │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │    │
│  │  │ Auth Store  │  │ API Client  │  │ Route Guards    │  │    │
│  │  │ (állapot)   │  │ (fetch)     │  │ (layout-alapú)  │  │    │
│  │  └─────────────┘  └─────────────┘  └─────────────────┘  │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ HTTP (fetch)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Apache + PHP Szerver                         │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                   PHP Proxy Backend                     │    │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │    │
│  │  │ Session     │  │ Token       │  │ Request         │  │    │
│  │  │ Manager     │  │ Handler     │  │ Forwarder       │  │    │
│  │  └─────────────┘  └─────────────┘  └─────────────────┘  │    │
│  │  ┌─────────────────────────────────────────────────────┐│    │
│  │  │              Titkos Adatok Tárolása                 ││    │
│  │  │  (API kulcsok, titkosítási kulcsok, rendszer ID)    ││    │
│  │  └─────────────────────────────────────────────────────┘│    │
│  └─────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              Statikus Fájlok (Svelte Build)             │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ HTTPS (cURL)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Külső API Backend                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │ Auth        │  │ Protected   │  │ Public                  │  │
│  │ Endpoints   │  │ Endpoints   │  │ Endpoints               │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

## Komponensek és Interfészek

### 1. Production Telepítési Struktúra

A végleges szerver struktúra biztosítja, hogy a titkos konfigurációs fájlok a document root-on KÍVÜL legyenek:

```
/var/www/myapp/                     # Alkalmazás gyökér (NEM public!)
├── .env                            # Környezeti változók (TITKOS!)
├── config/
│   └── bootstrap.php               # .env loader és Config osztály
├── vendor/                         # Composer csomagok (vlucas/phpdotenv)
│   └── ...
├── src/                            # PHP forráskód (NEM publikus!)
│   ├── Session.php
│   ├── TokenHandler.php
│   ├── RequestForwarder.php
│   └── Response.php
└── public_html/                    # DOCUMENT ROOT (csak ez publikus!)
    ├── index.html                  # Svelte SPA belépési pont
    ├── _app/                       # Svelte build assets
    │   ├── immutable/
    │   │   ├── chunks/
    │   │   └── entry/
    │   └── version.json
    ├── api/                        # PHP Proxy végpont
    │   ├── index.php               # Fő router (include-olja a src/-t)
    │   └── .htaccess               # URL rewrite szabályok
    ├── favicon.png
    └── .htaccess                   # Gyökér Apache konfig
```

**Fontos**: A `.env` fájl tartalmazza az ÖSSZES titkos adatot. A `config/bootstrap.php` csak betölti a `.env`-et és definiálja a Config osztályt - nem tárol semmilyen titkot!

### 2. .env Fájl Struktúra

A `vlucas/phpdotenv` csomag használatával kezeljük a környezeti változókat:

```env
# /var/www/myapp/.env

# Külső API konfiguráció
EXTERNAL_API_URL=https://api.example.com
EXTERNAL_API_TIMEOUT=30

# Titkosítási kulcsok
ENCRYPTION_KEY=your-32-character-encryption-key
SYSTEM_ID=your-system-identifier

# Session konfiguráció
SESSION_LIFETIME=3600
SESSION_NAME=myapp_session

# Rate limiting
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60

# Debug mód (production-ben false!)
DEBUG_MODE=false
```

### 3. Svelte Frontend Fejlesztési Struktúra

```
frontend/                           # Fejlesztési mappa
├── src/
│   ├── routes/
│   │   ├── +layout.svelte          # Gyökér layout
│   │   ├── +layout.js              # SSG konfiguráció
│   │   ├── +page.svelte            # Főoldal (publikus)
│   │   ├── login/
│   │   │   └── +page.svelte        # Bejelentkezési oldal
│   │   ├── (protected)/            # Védett route csoport
│   │   │   ├── +layout.svelte      # Védett layout (auth guard)
│   │   │   ├── dashboard/
│   │   │   │   └── +page.svelte
│   │   │   └── profile/
│   │   │       └── +page.svelte
│   │   └── (admin)/                # Admin route csoport
│   │       ├── +layout.svelte      # Admin layout (admin jogosultság)
│   │       ├── users/
│   │       │   └── +page.svelte
│   │       └── stats/
│   │           └── +page.svelte
│   ├── lib/
│   │   ├── api/
│   │   │   └── client.ts           # API kliens
│   │   ├── auth/
│   │   │   ├── store.svelte.ts     # Auth állapot (Svelte 5 runes)
│   │   │   ├── guard.ts            # Route guard logika
│   │   │   └── permissions.ts      # Jogosultság kezelés
│   │   └── components/
│   │       └── PermissionGate.svelte
│   └── app.html
├── static/
├── svelte.config.js
├── vite.config.js
└── package.json
```

### 4. PHP Backend Fejlesztési Struktúra

```
backend/                            # Fejlesztési mappa
├── .env.example                    # Példa .env fájl (commitolható!)
├── composer.json                   # Composer függőségek
├── config/
│   └── bootstrap.php               # .env loader és Config osztály
├── src/
│   ├── Session.php                 # Session kezelés
│   ├── TokenHandler.php            # Token kezelés és megújítás
│   ├── RequestForwarder.php        # Kérés továbbítás
│   ├── Response.php                # Válasz kezelés
│   ├── RateLimiter.php             # Rate limiting
│   └── CsrfProtection.php          # CSRF védelem
└── public/
    ├── index.php                   # Fő belépési pont
    └── .htaccess                   # Apache konfiguráció
```

### 5. Build és Deploy Folyamat

```bash
# 1. Frontend build
cd frontend
npm run build
# Kimenet: frontend/build/

# 2. Fájlok másolása a szerverre
# Frontend -> public_html/
scp -r frontend/build/* user@server:/var/www/myapp/public_html/

# PHP src -> src/
scp -r backend/src/* user@server:/var/www/myapp/src/

# PHP public -> public_html/api/
scp -r backend/public/* user@server:/var/www/myapp/public_html/api/

# Config -> config/
scp backend/config/* user@server:/var/www/myapp/config/

# Composer vendor (vagy composer install a szerveren)
scp -r backend/vendor user@server:/var/www/myapp/

# 3. .env fájl létrehozása a szerveren (manuálisan!)
# SOHA ne commitold a .env fájlt!
```

### 6. Apache Virtual Host Konfiguráció

```apache
# /etc/apache2/sites-available/myapp.conf

<VirtualHost *:443>
    ServerName myapp.example.com
    DocumentRoot /var/www/myapp/public_html

    # SSL konfiguráció
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem

    <Directory /var/www/myapp/public_html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # SPA fallback - minden nem létező fájl -> index.html
        FallbackResource /index.html
    </Directory>

    # PHP API végpont
    <Directory /var/www/myapp/public_html/api>
        AllowOverride All

        # PHP beállítások
        php_value session.cookie_httponly 1
        php_value session.cookie_secure 1
        php_value session.cookie_samesite Strict
    </Directory>

    # Titkos mappák védelme (extra biztonság)
    <DirectoryMatch "^/var/www/myapp/(config|src|vendor)">
        Require all denied
    </DirectoryMatch>

    # .env és egyéb titkos fájlok védelme
    <FilesMatch "^\.env|\.git|composer\.(json|lock)$">
        Require all denied
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/myapp_error.log
    CustomLog ${APACHE_LOG_DIR}/myapp_access.log combined
</VirtualHost>
```

### 7. PHP Proxy .htaccess (public_html/api/)

```apache
# /var/www/myapp/public_html/api/.htaccess

RewriteEngine On
RewriteBase /api/

# Minden kérés az index.php-ra
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# CORS headers (ha szükséges)
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-CSRF-Token"
</IfModule>

# PHP fájlok védelme
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>
```

### 8. PHP Bootstrap (.env loader)

A `.env` fájl tartalmazza az összes titkos adatot. A `config/bootstrap.php` betölti ezeket és definiálja a Config osztályt:

```php
<?php
// /var/www/myapp/config/bootstrap.php

// Composer autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// .env betöltése
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Kötelező változók ellenőrzése
$dotenv->required([
    'EXTERNAL_API_URL',
    'ENCRYPTION_KEY',
    'SYSTEM_ID'
])->notEmpty();

// Konfiguráció osztály
class Config {
    public static function get(string $key, $default = null) {
        return $_ENV[$key] ?? $default;
    }

    public static function getExternalApiUrl(): string {
        return $_ENV['EXTERNAL_API_URL'];
    }

    public static function getEncryptionKey(): string {
        return $_ENV['ENCRYPTION_KEY'];
    }

    public static function getSystemId(): string {
        return $_ENV['SYSTEM_ID'];
    }

    public static function getSessionLifetime(): int {
        return (int) ($_ENV['SESSION_LIFETIME'] ?? 3600);
    }

    public static function isDebugMode(): bool {
        return filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function getRateLimitRequests(): int {
        return (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 100);
    }

    public static function getRateLimitWindow(): int {
        return (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60);
    }
}
```

**Megjegyzés**: Ez a fájl NEM tárol titkokat - csak betölti a `.env`-ből és kényelmes hozzáférést biztosít hozzájuk.

### 9. API Kliens Interfész (TypeScript)

```typescript
// src/lib/api/client.ts

interface ApiResponse<T> {
  success: boolean;
  data?: T;
  error?: {
    code: string;
    message: string;
  };
}

interface ApiClient {
  get<T>(endpoint: string): Promise<ApiResponse<T>>;
  post<T>(endpoint: string, data: unknown): Promise<ApiResponse<T>>;
  put<T>(endpoint: string, data: unknown): Promise<ApiResponse<T>>;
  patch<T>(endpoint: string, data: unknown): Promise<ApiResponse<T>>;
  delete<T>(endpoint: string): Promise<ApiResponse<T>>;
  upload<T>(endpoint: string, files: FileList, data?: unknown): Promise<ApiResponse<T>>;
}
```

### 10. Auth Store Interfész (Svelte 5 Runes)

```typescript
// src/lib/auth/store.svelte.ts

interface User {
  id: string;
  email: string;
  name: string;
  permissions: string[];
}

interface AuthState {
  isAuthenticated: boolean;
  user: User | null;
  isLoading: boolean;
}

// Svelte 5 runes használata
let authState = $state<AuthState>({
  isAuthenticated: false,
  user: null,
  isLoading: true
});

// Derived állapotok
let isAdmin = $derived(authState.user?.permissions.includes('admin') ?? false);
let hasPermission = (permission: string) => $derived(
  authState.user?.permissions.includes(permission) ?? false
);
```

### 11. PHP Proxy Interfészek

```php
// api/lib/TokenHandler.php

interface TokenHandlerInterface {
    public function getAccessToken(): ?string;
    public function getRefreshToken(): ?string;
    public function setTokens(string $accessToken, string $refreshToken): void;
    public function clearTokens(): void;
    public function refreshAccessToken(): bool;
}

// api/lib/RequestForwarder.php

interface RequestForwarderInterface {
    public function forward(
        string $method,
        string $endpoint,
        array $data = [],
        array $files = []
    ): array;
}
```

### 12. Composer Függőségek

```json
{
    "name": "myapp/php-proxy",
    "description": "PHP Proxy Backend for Svelte Frontend",
    "require": {
        "php": ">=8.1",
        "vlucas/phpdotenv": "^5.6"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

## Adatmodellek

### 1. Felhasználói Adatok

```typescript
interface User {
  id: string;
  email: string;
  name: string;
  permissions: string[];
  createdAt: string;
  updatedAt: string;
}
```

### 2. Auth Válaszok

```typescript
// Bejelentkezési válasz (PHP proxy-tól a frontend felé)
interface LoginResponse {
  success: boolean;
  user?: User;
  error?: string;
}

// Külső API válasz (PHP proxy-nak)
interface ExternalAuthResponse {
  access_token: string;
  refresh_token: string;
  expires_in: number;
  user: User;
}
```

### 3. Session Adatok (PHP)

```php
// PHP Session struktúra
$_SESSION = [
    'access_token' => 'jwt_access_token_here',
    'refresh_token' => 'jwt_refresh_token_here',
    'token_expires_at' => 1234567890,
    'user' => [
        'id' => 'user_id',
        'email' => 'user@example.com',
        'name' => 'User Name',
        'permissions' => ['read', 'write', 'admin']
    ],
    'csrf_token' => 'random_csrf_token'
];
```

### 4. Jogosultságok

```typescript
// Előre definiált jogosultságok
type Permission =
  | 'read'           // Olvasási jogosultság
  | 'write'          // Írási jogosultság
  | 'delete'         // Törlési jogosultság
  | 'admin'          // Admin jogosultság
  | 'users:manage'   // Felhasználók kezelése
  | 'stats:view'     // Statisztikák megtekintése
  | string;          // Egyéb egyedi jogosultságok

// Route-jogosultság mapping
const routePermissions: Record<string, Permission[]> = {
  '/admin': ['admin'],
  '/admin/users': ['admin', 'users:manage'],
  '/admin/stats': ['admin', 'stats:view']
};
```

## Helyesség Tulajdonságok

*A helyesség tulajdonságok olyan jellemzők vagy viselkedések, amelyeknek igaznak kell lenniük a rendszer összes érvényes végrehajtása során - lényegében formális állítások arról, hogy mit kell tennie a rendszernek. A tulajdonságok hidat képeznek az ember által olvasható specifikációk és a géppel ellenőrizhető helyességi garanciák között.*

### 1. Tulajdonság: Token Biztonság
*Bármely* API válasz esetén, a PHP_Proxy SOHA nem adhat vissza access_token vagy refresh_token értéket a Frontend felé.
**Validálja: Követelmények 10.1**

### 2. Tulajdonság: Session Token Tárolás Körforgás
*Bármely* sikeres bejelentkezés esetén, ha a Külső_API tokeneket ad vissza, majd a PHP_Proxy tárolja őket a session-ben, akkor a session-ből visszaolvasott tokeneknek meg kell egyezniük az eredetileg tárolt tokenekkel.
**Validálja: Követelmények 3.2**

### 3. Tulajdonság: Automatikus Token Megújítás
*Bármely* 401-es válasz esetén a Külső_API-tól, ha van érvényes refresh_token a session-ben, a PHP_Proxy-nak meg kell próbálnia megújítani az access_token-t, és sikeres megújítás esetén újra kell próbálnia az eredeti kérést.
**Validálja: Követelmények 4.1, 4.2**

### 4. Tulajdonság: Védett Útvonal Átirányítás
*Bármely* védett útvonalra történő navigáció esetén, ha a felhasználó nincs autentikálva, a Frontend-nek át kell irányítania a bejelentkezési oldalra, megőrizve a célzott URL-t.
**Validálja: Követelmények 5.2, 5.5**

### 5. Tulajdonság: Hierarchikus Route Védelem
*Bármely* route prefix esetén, amely védettként van jelölve (pl. /admin), az összes alatta lévő útvonalnak (pl. /admin/users, /admin/stats) szintén védettnek kell lennie.
**Validálja: Követelmények 5.1, 5.4**

### 6. Tulajdonság: Jogosultság-alapú UI Megjelenítés
*Bármely* jogosultság-korlátozott UI elem esetén, ha a felhasználónak nincs meg a szükséges jogosultsága, az elemnek rejtettnek vagy letiltottnak kell lennie.
**Validálja: Követelmények 6.3, 6.5**

### 7. Tulajdonság: API Kliens Egységesség
*Bármely* HTTP metódus (GET, POST, PUT, DELETE, PATCH) esetén, az API kliens-nek ugyanazt a válasz formátumot kell visszaadnia (ApiResponse<T>).
**Validálja: Követelmények 7.1**

### 8. Tulajdonság: CSRF Token Validáció
*Bármely* állapotváltoztató kérés (POST, PUT, DELETE, PATCH) esetén, a PHP_Proxy-nak ellenőriznie kell a CSRF tokent, és érvénytelen token esetén el kell utasítania a kérést.
**Validálja: Követelmények 8.5**

### 9. Tulajdonság: Session Lejárat Kezelés
*Bármely* lejárt session esetén, a PHP_Proxy-nak 401-et kell visszaadnia, és a Frontend-nek át kell irányítania a felhasználót a bejelentkezési oldalra.
**Validálja: Követelmények 4.4, 4.5, 8.2**

### 10. Tulajdonság: Fájlfeltöltés Továbbítás
*Bármely* fájlfeltöltés esetén, a PHP_Proxy-nak sikeresen továbbítania kell a fájlokat a Külső_API felé, megőrizve a fájl metaadatokat (név, típus, méret).
**Validálja: Követelmények 2.6, 7.5**

## Hibakezelés

### Frontend Hibakezelés

```typescript
// src/lib/api/client.ts

enum ErrorCode {
  NETWORK_ERROR = 'NETWORK_ERROR',
  UNAUTHORIZED = 'UNAUTHORIZED',
  FORBIDDEN = 'FORBIDDEN',
  NOT_FOUND = 'NOT_FOUND',
  VALIDATION_ERROR = 'VALIDATION_ERROR',
  SERVER_ERROR = 'SERVER_ERROR'
}

interface ApiError {
  code: ErrorCode;
  message: string;
  details?: Record<string, string[]>;
}

// Hibakezelési stratégia
async function handleApiError(response: Response): Promise<never> {
  if (response.status === 401) {
    // Átirányítás bejelentkezésre
    authStore.logout();
    goto('/login?redirect=' + encodeURIComponent(window.location.pathname));
  }

  const error = await response.json();
  throw new ApiError(error);
}
```

### PHP Proxy Hibakezelés

```php
// api/lib/Response.php

class ApiResponse {
    public static function error(int $statusCode, string $code, string $message): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ]);
        exit;
    }

    public static function success($data): void {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }
}
```

### Hibakódok Mapping

| HTTP Státusz | Hibakód | Leírás |
|--------------|---------|--------|
| 400 | VALIDATION_ERROR | Érvénytelen bemeneti adatok |
| 401 | UNAUTHORIZED | Nincs autentikáció vagy lejárt token |
| 403 | FORBIDDEN | Nincs jogosultság |
| 404 | NOT_FOUND | Erőforrás nem található |
| 429 | RATE_LIMITED | Túl sok kérés |
| 500 | SERVER_ERROR | Szerver hiba |

## Tesztelési Stratégia

### Unit Tesztek

A unit tesztek specifikus példákat és edge case-eket tesztelnek:

1. **Auth Store tesztek**
   - Bejelentkezési állapot változás
   - Jogosultság ellenőrzés
   - Kijelentkezés

2. **API Client tesztek**
   - HTTP metódusok helyes hívása
   - Hibakezelés
   - Válasz feldolgozás

3. **PHP Token Handler tesztek**
   - Token tárolás és visszaolvasás
   - Token megújítás logika
   - Session kezelés

### Property-Based Tesztek

A property-based tesztek univerzális tulajdonságokat validálnak sok generált bemenettel:

1. **Token Biztonság Property Test**
   - Generálunk véletlenszerű API válaszokat
   - Ellenőrizzük, hogy soha nem tartalmazzák a tokeneket

2. **Route Védelem Property Test**
   - Generálunk véletlenszerű útvonalakat
   - Ellenőrizzük a hierarchikus védelem működését

3. **Jogosultság Property Test**
   - Generálunk véletlenszerű felhasználókat és jogosultságokat
   - Ellenőrizzük a UI elemek megfelelő megjelenítését

### Tesztelési Keretrendszer

- **Frontend**: Vitest + @testing-library/svelte
- **PHP**: PHPUnit
- **Property-Based Testing**: fast-check (JavaScript), PHPUnit (PHP)

### Teszt Konfiguráció

```typescript
// vitest.config.ts
export default defineConfig({
  test: {
    // Minimum 100 iteráció property tesztekhez
    fuzz: {
      iterations: 100
    }
  }
});
```

## Külső API Végpontok (Javasolt)

A rendszer működéséhez a következő végpontokra van szükség a külső API-ban:

### Autentikációs Végpontok

```
POST /auth/login
  Body: { email: string, password: string }
  Response: { access_token, refresh_token, expires_in, user }

POST /auth/refresh
  Body: { refresh_token: string }
  Response: { access_token, refresh_token, expires_in }

POST /auth/logout
  Headers: Authorization: Bearer <access_token>
  Response: { success: true }

GET /auth/me
  Headers: Authorization: Bearer <access_token>
  Response: { user: { id, email, name, permissions } }
```

### Védett Végpontok Viselkedése

- Minden védett végpont ellenőrzi a Bearer tokent
- Érvénytelen vagy hiányzó token esetén 401 Unauthorized
- Jogosultság hiánya esetén 403 Forbidden
