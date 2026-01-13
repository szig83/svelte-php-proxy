# Tervezési Dokumentum: Frontend Hibanaplózás

## Áttekintés

A frontend hibanaplózási rendszer célja, hogy a SvelteKit alkalmazásban keletkező hibákat (JavaScript hibák, API hibák, kezeletlen kivételek) összegyűjtse, kontextussal gazdagítsa, és egy PHP backend szolgáltatáson keresztül perzisztensen tárolja. A rendszer lehetővé teszi a hibák későbbi visszanézését és elemzését egy admin felületen keresztül.

A megoldás a következő fő komponensekből áll:
- **Frontend Error Logger modul** - Hibák elkapása, kontextus gyűjtés, küldés
- **PHP Backend endpoint** - Hibák fogadása, validálása, tárolása
- **Error Viewer oldal** - Admin felület a hibák megtekintéséhez

## Architektúra

```mermaid
flowchart TB
    subgraph Frontend["Frontend (SvelteKit)"]
        GEH[Globális Hibakezelő]
        API[API Kliens]
        MC[Manuális Hívások]
        EL[Error Logger Modul]
        LS[LocalStorage Queue]

        GEH --> EL
        API --> EL
        MC --> EL
        EL --> LS
    end

    subgraph Backend["Backend (PHP)"]
        EP[/api/errors Endpoint]
        VAL[Validátor]
        STORE[Hiba Tároló]
        FILE[(errors.json)]

        EP --> VAL
        VAL --> STORE
        STORE --> FILE
    end

    subgraph Admin["Admin Felület"]
        EV[Hiba Nézegető Oldal]
        LIST[Hiba Lista API]

        EV --> LIST
        LIST --> STORE
    end

    EL -->|POST /api/errors| EP
    LS -->|Retry Queue| EP
```

## Komponensek és Interfészek

### 1. Error Logger Modul (Frontend)

A központi modul, amely összefogja a hibanaplózás logikáját.

**Fájl:** `frontend/src/lib/errors/logger.ts`

```typescript
interface ErrorLoggerConfig {
    enabled: boolean;              // Naplózás engedélyezve
    endpoint: string;              // Backend API URL
    maxRetries: number;            // Maximum újrapróbálkozások száma
    retryDelay: number;            // Újrapróbálkozás késleltetése (ms)
    rateLimit: {
        maxErrors: number;         // Maximum hibák száma az időablakban
        windowMs: number;          // Időablak hossza (ms)
    };
    appVersion?: string;           // Alkalmazás verzió
    isDevelopment: boolean;        // Fejlesztői mód
}

interface ErrorEntry {
    id: string;                    // Egyedi azonosító
    type: 'javascript' | 'api' | 'manual';  // Hiba típusa
    severity: 'error' | 'warning' | 'info'; // Súlyosság
    message: string;               // Hiba üzenet
    stack?: string;                // Stack trace
    context: ErrorContext;         // Kontextus információk
    timestamp: string;             // Időbélyeg (ISO 8601)
}

interface ErrorContext {
    url: string;                   // Aktuális oldal URL
    userAgent: string;             // Böngésző user agent
    userId?: string;               // Felhasználó azonosító
    appVersion?: string;           // Alkalmazás verzió
    extra?: Record<string, unknown>;  // Egyedi kontextus
}

interface ErrorLogger {
    // Inicializálás
    init(config: Partial<ErrorLoggerConfig>): void;

    // Manuális naplózás
    log(error: Error | string, extra?: Record<string, unknown>): void;
    warn(message: string, extra?: Record<string, unknown>): void;
    info(message: string, extra?: Record<string, unknown>): void;

    // API hiba naplózás
    logApiError(endpoint: string, status: number, error: ApiError): void;

    // Globális handler-ek regisztrálása
    registerGlobalHandlers(): void;

    // Retry queue feldolgozása
    processRetryQueue(): Promise<void>;
}
```

### 2. Globális Hibakezelők

A globális hibakezelők a `window.onerror` és `window.onunhandledrejection` eseményeket figyelik.

**Fájl:** `frontend/src/lib/errors/handlers.ts`

```typescript
function setupGlobalErrorHandler(logger: ErrorLogger): void {
    if (typeof window === 'undefined') return;

    // JavaScript hibák elkapása
    window.onerror = (message, source, lineno, colno, error) => {
        logger.log(error || new Error(String(message)), {
            source,
            lineno,
            colno
        });
        return false; // Ne szakítsa meg a default kezelést
    };

    // Kezeletlen Promise rejection elkapása
    window.onunhandledrejection = (event) => {
        const error = event.reason instanceof Error
            ? event.reason
            : new Error(String(event.reason));
        logger.log(error, { type: 'unhandledrejection' });
    };
}
```

### 3. Rate Limiter (Sebességkorlátozó)

Megakadályozza, hogy túl sok hiba kerüljön elküldésre rövid időn belül.

**Fájl:** `frontend/src/lib/errors/rate-limiter.ts`

```typescript
interface RateLimiter {
    canSend(): boolean;      // Küldhető-e hiba
    recordSend(): void;      // Küldés rögzítése
    reset(): void;           // Számláló nullázása
}

class SlidingWindowRateLimiter implements RateLimiter {
    private timestamps: number[] = [];
    private maxErrors: number;
    private windowMs: number;

    constructor(maxErrors: number, windowMs: number) {
        this.maxErrors = maxErrors;
        this.windowMs = windowMs;
    }

    canSend(): boolean {
        this.cleanup();
        return this.timestamps.length < this.maxErrors;
    }

    recordSend(): void {
        this.timestamps.push(Date.now());
    }

    private cleanup(): void {
        const cutoff = Date.now() - this.windowMs;
        this.timestamps = this.timestamps.filter(t => t > cutoff);
    }
}
```

### 4. Retry Queue (Újrapróbálkozási Sor)

A sikertelen küldéseket localStorage-ban tárolja és később újrapróbálja.

**Fájl:** `frontend/src/lib/errors/retry-queue.ts`

```typescript
interface QueuedError {
    entry: ErrorEntry;       // A hiba bejegyzés
    attempts: number;        // Próbálkozások száma
    lastAttempt: number;     // Utolsó próbálkozás időpontja
}

interface RetryQueue {
    add(entry: ErrorEntry): void;           // Hiba hozzáadása
    getAll(): QueuedError[];                // Összes elem lekérése
    remove(id: string): void;               // Elem törlése
    clear(): void;                          // Sor ürítése
    incrementAttempt(id: string): void;     // Próbálkozás számláló növelése
}

const STORAGE_KEY = 'error_retry_queue';
const MAX_QUEUE_SIZE = 50;
```

### 5. PHP Backend Endpoint

A backend endpoint fogadja és tárolja a hibákat.

**Fájl:** `backend/src/ErrorLogger.php`

```php
<?php

class ErrorLogger {
    private string $storageFile;    // Tárolási fájl útvonala
    private int $maxEntries;        // Maximum tárolt hibák száma

    public function __construct(string $storageFile, int $maxEntries = 1000) {
        $this->storageFile = $storageFile;
        $this->maxEntries = $maxEntries;
    }

    /**
     * Hiba naplózása
     */
    public function log(array $errorData): string {
        $this->validate($errorData);

        $entry = [
            'id' => $this->generateId(),
            'type' => $errorData['type'],
            'severity' => $errorData['severity'],
            'message' => $errorData['message'],
            'stack' => $errorData['stack'] ?? null,
            'context' => $errorData['context'],
            'timestamp' => $errorData['timestamp'],
            'receivedAt' => date('c')
        ];

        $this->store($entry);

        return $entry['id'];
    }

    /**
     * Hibák lekérdezése szűrőkkel
     */
    public function getErrors(array $filters = []): array {
        // Implementáció
    }

    /**
     * Egy hiba lekérdezése ID alapján
     */
    public function getError(string $id): ?array {
        // Implementáció
    }

    /**
     * Bejövő adatok validálása
     */
    private function validate(array $data): void {
        // Kötelező mezők ellenőrzése
    }

    /**
     * Hiba tárolása JSON fájlba
     */
    private function store(array $entry): void {
        // Implementáció
    }

    /**
     * Egyedi azonosító generálása
     */
    private function generateId(): string {
        return uniqid('err_', true);
    }
}
```

### 6. Hiba Nézegető Komponens

Svelte komponens a hibák megtekintéséhez.

**Fájl:** `frontend/src/routes/(protected)/admin/errors/+page.svelte`

```svelte
<script lang="ts">
    interface ErrorListItem {
        id: string;
        type: string;
        severity: string;
        message: string;
        timestamp: string;
    }

    let errors = $state<ErrorListItem[]>([]);
    let selectedError = $state<ErrorEntry | null>(null);
    let filters = $state({
        type: '',           // Típus szűrő
        dateFrom: '',       // Dátum kezdete
        dateTo: ''          // Dátum vége
    });
</script>
```

## Adatmodellek

### ErrorEntry (Frontend → Backend)

```typescript
interface ErrorEntry {
    id: string;                    // Egyedi azonosító (frontend generálja)
    type: 'javascript' | 'api' | 'manual';  // Hiba típusa
    severity: 'error' | 'warning' | 'info'; // Súlyosság
    message: string;               // Hiba üzenet
    stack?: string;                // Stack trace (ha elérhető)
    context: {
        url: string;               // Aktuális oldal URL
        userAgent: string;         // Böngésző user agent
        userId?: string;           // Bejelentkezett felhasználó ID
        appVersion?: string;       // Alkalmazás verzió
        extra?: Record<string, unknown>;  // Egyedi kontextus
    };
    timestamp: string;             // ISO 8601 formátum
}
```

### StoredError (Backend)

```typescript
interface StoredError extends ErrorEntry {
    receivedAt: string;            // Backend fogadás időpontja
    clientIp?: string;             // Kliens IP (opcionális)
}
```

### ErrorListResponse (Backend → Frontend)

```typescript
interface ErrorListResponse {
    success: boolean;
    data: {
        errors: StoredError[];     // Hibák listája
        total: number;             // Összes találat
        page: number;              // Aktuális oldal
        pageSize: number;          // Oldalméret
    };
}
```

## Helyességi Tulajdonságok (Correctness Properties)

*A property egy olyan jellemző vagy viselkedés, amelynek igaznak kell lennie a rendszer összes érvényes végrehajtása során - lényegében egy formális állítás arról, hogy mit kell tennie a rendszernek. A property-k hidat képeznek az ember által olvasható specifikációk és a géppel ellenőrizhető helyességi garanciák között.*

### Property 1: Hiba Kontextus Teljessége

*Bármely* naplózott hiba bejegyzés esetén a context objektumnak tartalmaznia KELL az összes kötelező mezőt: url (nem üres string), userAgent (nem üres string), és timestamp (érvényes ISO 8601 formátum).

**Validálja: Követelmények 3.1, 3.2, 3.3**

### Property 2: Stack Trace Megőrzése

*Bármely* Error objektum esetén, amelynek van stack property-je és naplózásra kerül, az eredményül kapott ErrorEntry-nek tartalmaznia KELL a stack trace-t a stack mezőjében.

**Validálja: Követelmények 1.3**

### Property 3: API Hiba Információ Teljessége

*Bármely* API hiba esetén, amely a logApiError() metódussal kerül naplózásra, az eredményül kapott ErrorEntry-nek tartalmaznia KELL az endpoint URL-t és a HTTP státuszkódot a context.extra mezőjében.

**Validálja: Követelmények 2.1, 2.3**

### Property 4: Rate Limiter Helyessége

*Bármely* N darab hibaküldés sorozat esetén T időablakon belül, ahol N meghaladja a konfigurált maxErrors limitet, a rate limiter-nek blokkolnia KELL a további küldéseket, amíg az időablak le nem jár.

**Validálja: Követelmények 4.3**

### Property 5: Retry Queue Perzisztencia

*Bármely* sikertelen hibaküldés esetén a hiba bejegyzésnek be KELL kerülnie a retry queue-ba a localStorage-ban, és visszakereshetőnek KELL lennie oldal újratöltés után is.

**Validálja: Követelmények 4.2, 4.4**

### Property 6: Backend Validáció Helyessége

*Bármely* a backendre küldött hiba adat esetén, HA az adat hiányzó kötelező mezőket tartalmaz (type, message, context), AKKOR a backendnek validációs hiba választ KELL visszaadnia.

**Validálja: Követelmények 5.1**

### Property 7: Backend Tárolás Round-Trip

*Bármely* érvényes hiba bejegyzés esetén, amely a backend-en keresztül tárolásra kerül, a visszakapott ID alapján történő lekérdezésnek egy ekvivalens hiba bejegyzést KELL visszaadnia az összes eredeti mezővel.

**Validálja: Követelmények 5.2, 5.3**

### Property 8: Szűrés Helyessége

*Bármely* típus szűrővel rendelkező hiba lista lekérdezés esetén az összes visszaadott hibának a megadott típusúnak KELL lennie. *Bármely* dátum tartomány szűrővel rendelkező lekérdezés esetén az összes visszaadott hiba időbélyegének a megadott tartományon belül KELL lennie.

**Validálja: Követelmények 6.4, 6.5**

### Property 9: Manuális Naplózás Paraméter Megőrzése

*Bármely* manuális log hívás esetén severity-vel és extra kontextussal, az eredményül kapott ErrorEntry-nek a megadott severity-vel KELL rendelkeznie és tartalmaznia KELL az összes extra kontextus mezőt.

**Validálja: Követelmények 7.2, 7.3**

### Property 10: Konfiguráció Hatékonysága

*Bármely* ErrorLogger példány esetén enabled=false beállítással, a log() hívás NEM eredményezhet HTTP kérést vagy localStorage írást. *Bármely* konfigurált rate limit érték esetén pontosan azokat az értékeket KELL használnia a rate limiter-nek.

**Validálja: Követelmények 8.1, 8.3**

## Hibakezelés

### Frontend Hibakezelés

1. **Logger Inicializálási Hibák**
   - Ha a logger inicializálása sikertelen (pl. localStorage nem elérhető), a logger "degraded mode"-ban működik: csak konzolra logol
   - A hiba nem szakítja meg az alkalmazás működését

2. **Hálózati Hibák Küldés Közben**
   - Sikertelen küldés esetén a hiba bekerül a retry queue-ba
   - Maximum 3 retry kísérlet, exponenciális backoff-fal (1s, 2s, 4s)
   - Ha minden retry sikertelen, a hiba localStorage-ban marad a következő session-ig

3. **Rate Limiting**
   - Ha a rate limit elérve, a hibák localStorage-ba kerülnek
   - A queue feldolgozása a következő oldalbetöltéskor vagy manuálisan triggerelhető

4. **LocalStorage Kvóta Túllépés**
   - Ha a localStorage megtelt, a legrégebbi queue elemek törlődnek
   - Maximum 50 elem tárolható a queue-ban

### Backend Hibakezelés

1. **Validációs Hibák**
   - Hiányzó vagy invalid mezők esetén 422 Validation Error response
   - Részletes hibaüzenet a hibás mezőkről

2. **Tárolási Hibák**
   - Ha a fájl írás sikertelen, 500 Server Error response
   - A hiba logolásra kerül a szerver error log-ba

3. **Fájlméret Kezelés**
   - Maximum 1000 hiba tárolása
   - Ha elérve, a legrégebbi hibák törlődnek (FIFO)

## Tesztelési Stratégia

### Unit Tesztek

A unit tesztek specifikus példákat és edge case-eket tesztelnek:

- Error Logger inicializálás különböző konfigurációkkal
- Rate limiter működése határértékeknél
- Retry queue localStorage műveletek
- Backend validáció különböző invalid inputokkal
- Filter logika edge case-ek (üres lista, nincs találat)

### Property-Based Tesztek

A property tesztek univerzális tulajdonságokat ellenőriznek sok generált inputtal:

**Frontend (TypeScript - fast-check)**
- Property 1: Hiba Kontextus Teljessége
- Property 2: Stack Trace Megőrzése
- Property 3: API Hiba Információ Teljessége
- Property 4: Rate Limiter Helyessége
- Property 5: Retry Queue Perzisztencia
- Property 9: Manuális Naplózás Paraméter Megőrzése
- Property 10: Konfiguráció Hatékonysága

**Backend (PHP - Eris)**
- Property 6: Backend Validáció Helyessége
- Property 7: Backend Tárolás Round-Trip
- Property 8: Szűrés Helyessége

### Teszt Konfiguráció

- Minimum 100 iteráció property tesztenként
- Minden property teszt hivatkozik a design dokumentum property-jére
- Tag formátum: `Feature: frontend-error-logging, Property N: [property szöveg]`

### Tesztelési Eszközök

- **Frontend**: Vitest + fast-check
- **Backend**: PHPUnit + Eris (property-based testing PHP-hoz)
