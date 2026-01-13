# Frontend Hibanaplózási Rendszer - Fejlesztői Dokumentáció

## Áttekintés

A frontend hibanaplózási rendszer automatikusan összegyűjti és tárolja a SvelteKit alkalmazásban keletkező hibákat (JavaScript hibák, API hibák, kezeletlen kivételek). A hibák egy PHP backend szolgáltatáson keresztül perzisztensen tárolódnak, és egy admin felületen visszanézhetők.

## Architektúra

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (SvelteKit)                     │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Globális  │  │  API Kliens │  │  Manuális Hívások   │  │
│  │  Hibakezelő │  │   Hibák     │  │  (log/warn/info)    │  │
│  └──────┬──────┘  └──────┬──────┘  └──────────┬──────────┘  │
│         │                │                    │             │
│         └────────────────┼────────────────────┘             │
│                          ▼                                  │
│              ┌───────────────────────┐                      │
│              │    Error Logger       │                      │
│              │  ┌─────────────────┐  │                      │
│              │  │  Rate Limiter   │  │                      │
│              │  └─────────────────┘  │                      │
│              │  ┌─────────────────┐  │                      │
│              │  │  Retry Queue    │◄─┼── localStorage       │
│              │  └─────────────────┘  │                      │
│              └───────────┬───────────┘                      │
└──────────────────────────┼──────────────────────────────────┘
                           │ POST /api/errors
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    Backend (PHP)                            │
│              ┌───────────────────────┐                      │
│              │    ErrorLogger.php    │                      │
│              │  ┌─────────────────┐  │                      │
│              │  │   Validáció     │  │                      │
│              │  └─────────────────┘  │                      │
│              │  ┌─────────────────┐  │                      │
│              │  │   JSON Tárolás  │──┼──► errors.json       │
│              │  └─────────────────┘  │                      │
│              └───────────────────────┘                      │
└─────────────────────────────────────────────────────────────┘
```

## Telepítés és Konfiguráció

### Frontend Inicializálás

Az Error Logger-t az alkalmazás indulásakor kell inicializálni, tipikusan a root layout-ban:

```typescript
// src/routes/+layout.svelte
<script lang="ts">
  import { onMount } from 'svelte';
  import { initErrorLogger, getErrorLogger } from '$lib/errors';

  onMount(() => {
    // Error Logger inicializálása
    const logger = initErrorLogger({
      enabled: true,
      endpoint: '/api/errors',
      isDevelopment: import.meta.env.DEV,
      appVersion: '1.0.0',
      rateLimit: {
        maxErrors: 10,
        windowMs: 60000  // 1 perc
      }
    });

    // Globális hibakezelők regisztrálása
    logger.registerGlobalHandlers();
  });
</script>
```

### Konfiguráció Opciók

| Opció | Típus | Alapértelmezett | Leírás |
|-------|-------|-----------------|--------|
| `enabled` | boolean | `true` | Naplózás engedélyezése |
| `endpoint` | string | `/api/errors` | Backend API URL |
| `maxRetries` | number | `3` | Maximum újrapróbálkozások száma |
| `retryDelay` | number | `1000` | Újrapróbálkozás késleltetése (ms) |
| `rateLimit.maxErrors` | number | `10` | Max hibák száma az időablakban |
| `rateLimit.windowMs` | number | `60000` | Időablak hossza (ms) |
| `appVersion` | string | - | Alkalmazás verzió |
| `isDevelopment` | boolean | `false` | Fejlesztői mód (konzol log) |

## Használat

### Automatikus Hibakezelés

A `registerGlobalHandlers()` hívás után a rendszer automatikusan elkapja:

1. **Kezeletlen JavaScript hibák** (`window.onerror`)
2. **Kezeletlen Promise rejection-ök** (`window.onunhandledrejection`)

### Manuális Naplózás

```typescript
import { logError, logWarning, logInfo } from '$lib/errors';

// Hiba naplózása
logError(new Error('Valami hiba történt'), {
  component: 'UserProfile',
  action: 'loadData'
});

// Figyelmeztetés
logWarning('Deprecated API használat', {
  endpoint: '/api/v1/users'
});

// Info
logInfo('Felhasználó bejelentkezett', {
  userId: '123'
});
```

### API Hiba Naplózás

Az API kliens automatikusan naplózza a hibás válaszokat:

```typescript
import { logApiError } from '$lib/errors';

// API hiba naplózása
logApiError('/api/users', 500, {
  message: 'Internal Server Error',
  code: 'SERVER_ERROR'
});
```

## Komponensek

### 1. Error Logger (`logger.ts`)

A központi modul, amely összefogja a hibanaplózás logikáját.

**Fő metódusok:**
- `init(config)` - Logger inicializálása
- `log(error, extra?)` - Hiba naplózása
- `warn(message, extra?)` - Figyelmeztetés naplózása
- `info(message, extra?)` - Info naplózása
- `logApiError(endpoint, status, error)` - API hiba naplózása
- `registerGlobalHandlers()` - Globális hibakezelők regisztrálása
- `processRetryQueue()` - Retry queue feldolgozása

### 2. Rate Limiter (`rate-limiter.ts`)

Sliding window algoritmussal korlátozza a küldések számát.

```typescript
import { SlidingWindowRateLimiter } from '$lib/errors';

const limiter = new SlidingWindowRateLimiter(10, 60000);

if (limiter.canSend()) {
  limiter.recordSend();
  // Küldés...
}
```

### 3. Retry Queue (`retry-queue.ts`)

LocalStorage alapú queue a sikertelen küldések tárolásához.

```typescript
import { LocalStorageRetryQueue } from '$lib/errors';

const queue = new LocalStorageRetryQueue();

// Hiba hozzáadása
queue.add(errorEntry);

// Összes elem lekérése
const items = queue.getAll();

// Elem törlése
queue.remove(errorId);
```

## Adatstruktúrák

### ErrorEntry

```typescript
interface ErrorEntry {
  id: string;                    // Egyedi azonosító
  type: 'javascript' | 'api' | 'manual';
  severity: 'error' | 'warning' | 'info';
  message: string;
  stack?: string;                // Stack trace
  context: {
    url: string;                 // Aktuális oldal URL
    userAgent: string;           // Böngésző user agent
    userId?: string;             // Felhasználó ID
    appVersion?: string;         // App verzió
    extra?: Record<string, unknown>;
  };
  timestamp: string;             // ISO 8601
}
```

## Backend API

### POST /api/errors

Új hiba naplózása.

**Request:**
```json
{
  "type": "javascript",
  "severity": "error",
  "message": "Cannot read property 'x' of undefined",
  "stack": "Error: Cannot read property...",
  "context": {
    "url": "https://example.com/page",
    "userAgent": "Mozilla/5.0...",
    "userId": "user123"
  },
  "timestamp": "2024-01-15T10:30:00.000Z"
}
```

**Response (201):**
```json
{
  "success": true,
  "id": "err_65a4b2c3d4e5f6"
}
```

### GET /api/errors

Hibák listázása szűrőkkel.

**Query paraméterek:**
- `type` - Szűrés típus szerint (javascript, api, manual)
- `dateFrom` - Kezdő dátum (ISO 8601)
- `dateTo` - Záró dátum (ISO 8601)
- `page` - Oldal szám (alapértelmezett: 1)
- `pageSize` - Oldalméret (alapértelmezett: 20, max: 100)

**Response:**
```json
{
  "success": true,
  "data": {
    "errors": [...],
    "total": 150,
    "page": 1,
    "pageSize": 20
  }
}
```

### GET /api/errors/{id}

Egy hiba részleteinek lekérése.

## Admin Felület

A hibák megtekinthetők az `/admin/errors` oldalon (admin jogosultság szükséges).

**Funkciók:**
- Hibák listázása időrendi sorrendben
- Szűrés típus szerint
- Szűrés dátum tartomány szerint
- Hiba részletek megtekintése (stack trace, kontextus)

## Hibakezelési Stratégiák

### Rate Limiting

Ha túl sok hiba keletkezik rövid időn belül (alapértelmezetten 10 hiba/perc), a további hibák a retry queue-ba kerülnek.

### Retry Mechanizmus

1. Sikertelen küldés → Retry queue-ba kerül
2. Következő oldalbetöltéskor újrapróbálkozás
3. Maximum 3 próbálkozás
4. Exponenciális backoff (1s, 2s, 4s)

### LocalStorage Kvóta

- Maximum 50 elem tárolható a queue-ban
- Ha megtelt, a legrégebbi elemek törlődnek (FIFO)
- Kvóta túllépés esetén a queue felét törli

### Degraded Mode

Ha a localStorage nem elérhető, a logger csak konzolra logol (fejlesztői módban).

## Tesztelés

### Frontend Tesztek

```bash
cd frontend
bun run vitest --run
```

**Teszt fájlok:**
- `logger.property.test.ts` - Error Logger property tesztek
- `rate-limiter.property.test.ts` - Rate Limiter property tesztek
- `retry-queue.property.test.ts` - Retry Queue property tesztek

### Backend Tesztek

```bash
cd backend
./vendor/bin/phpunit
```

**Teszt fájlok:**
- `ErrorLoggerValidationPropertyTest.php` - Validáció tesztek
- `ErrorLoggerStoragePropertyTest.php` - Tárolás tesztek
- `ErrorLoggerFilterPropertyTest.php` - Szűrés tesztek

## Példák

### Komponensben Történő Hibakezelés

```svelte
<script lang="ts">
  import { logError } from '$lib/errors';

  async function loadData() {
    try {
      const response = await fetch('/api/data');
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      return await response.json();
    } catch (error) {
      logError(error, {
        component: 'DataLoader',
        action: 'loadData'
      });
      throw error; // Továbbdobjuk a hibát
    }
  }
</script>
```

### Form Validációs Hiba Naplózása

```typescript
import { logWarning } from '$lib/errors';

function validateForm(data: FormData) {
  const errors = [];

  if (!data.email) {
    errors.push('Email kötelező');
  }

  if (errors.length > 0) {
    logWarning('Form validációs hiba', {
      errors,
      formId: 'registration'
    });
  }

  return errors;
}
```

### Egyedi Kontextus Hozzáadása

```typescript
import { logError } from '$lib/errors';

function processPayment(orderId: string) {
  try {
    // Fizetés feldolgozása...
  } catch (error) {
    logError(error, {
      orderId,
      paymentMethod: 'credit_card',
      amount: 9999,
      currency: 'HUF'
    });
  }
}
```

## Biztonsági Megfontolások

1. **Érzékeny adatok**: Ne logolj jelszavakat, token-eket vagy személyes adatokat
2. **Rate Limiting**: A backend is korlátozza a bejövő kéréseket
3. **Validáció**: Minden bejövő adat validálva van a backend-en
4. **Admin hozzáférés**: A hiba nézegető csak admin felhasználóknak elérhető

## Troubleshooting

### Hibák nem jelennek meg

1. Ellenőrizd, hogy az `enabled: true` be van-e állítva
2. Ellenőrizd a böngésző konzolt fejlesztői módban
3. Ellenőrizd a Network tab-ot a `/api/errors` kérésekhez

### Rate limit túl korlátozó

Növeld a `rateLimit.maxErrors` értéket vagy a `rateLimit.windowMs` időablakot.

### LocalStorage megtelt

A queue automatikusan törli a régi elemeket. Ha gyakran előfordul, csökkentsd a hibák számát vagy növeld a backend kapacitást.


## Backend PHP Hibák Naplózása

A rendszer automatikusan naplózza a backend PHP hibákat is, beleértve:
- PHP hibák (E_WARNING, E_NOTICE, E_DEPRECATED, stb.)
- Kivételek (Exception, Error)
- Fatal error-ok (E_ERROR, E_PARSE, E_COMPILE_ERROR)

### Hiba Típusok

| Típus | Szín | Leírás |
|-------|------|--------|
| `javascript` | 🟣 Lila | Frontend JavaScript hibák |
| `api` | 🟢 Zöld | API hívás hibák |
| `manual` | 🔵 Kék | Manuálisan naplózott hibák |
| `php` | 🩷 Rózsaszín | Backend PHP hibák |

### PHP Error Handler

A `PhpErrorHandler` osztály automatikusan regisztrálódik az alkalmazás indulásakor és elkapja:

1. **PHP hibák** (`set_error_handler`)
   - E_WARNING, E_NOTICE, E_DEPRECATED, stb.
   - Severity: warning vagy info

2. **Kivételek** (`set_exception_handler`)
   - Minden elkapott Exception és Error
   - Severity: error

3. **Fatal error-ok** (`register_shutdown_function`)
   - E_ERROR, E_PARSE, E_COMPILE_ERROR
   - Severity: error

### PHP Hiba Kontextus

A PHP hibák a következő extra kontextust tartalmazzák:

```json
{
  "extra": {
    "file": "/path/to/file.php",
    "line": 42,
    "errorType": "E_WARNING",
    "phpVersion": "8.5.1",
    "serverSoftware": "Apache/2.4",
    "requestMethod": "POST",
    "requestUri": "/api/users"
  }
}
```

### PHP Hiba Tesztelése

PHP hiba generálásához adj hozzá szándékos hibát a backend kódhoz:

```php
// Szintaktikai hiba tesztelése
trigger_error('Teszt PHP warning', E_USER_WARNING);

// Kivétel tesztelése
throw new \Exception('Teszt PHP kivétel');

// Undefined variable (E_WARNING)
echo $undefinedVariable;
```

Vagy hívj meg egy nem létező API endpoint-ot, ami hibát okoz.
