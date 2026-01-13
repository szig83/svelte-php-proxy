# Implementációs Terv: Frontend Hibanaplózás

## Áttekintés

Ez a dokumentum a frontend hibanaplózási rendszer implementációs lépéseit tartalmazza. A feladatok inkrementálisan építkeznek, először a core funkcionalitást valósítjuk meg, majd a kiegészítő funkciókat.

## Feladatok

- [x] 1. Frontend Error Logger alapok létrehozása
  - [x] 1.1 Error Logger típusok és interfészek definiálása
    - Létrehozni a `frontend/src/lib/errors/types.ts` fájlt
    - Definiálni az ErrorEntry, ErrorContext, ErrorLoggerConfig interfészeket
    - _Követelmények: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 1.2 Rate Limiter implementálása
    - Létrehozni a `frontend/src/lib/errors/rate-limiter.ts` fájlt
    - Implementálni a SlidingWindowRateLimiter osztályt
    - _Követelmények: 4.3_

  - [x] 1.3 Property teszt írása a Rate Limiter-hez
    - **Property 4: Rate Limiter Helyessége**
    - **Validálja: Követelmények 4.3**

  - [x] 1.4 Retry Queue implementálása
    - Létrehozni a `frontend/src/lib/errors/retry-queue.ts` fájlt
    - Implementálni a localStorage alapú queue kezelést
    - _Követelmények: 4.2, 4.4_

  - [x] 1.5 Property teszt írása a Retry Queue-hoz
    - **Property 5: Retry Queue Perzisztencia**
    - **Validálja: Követelmények 4.2, 4.4**

- [x] 2. Error Logger core funkcionalitás
  - [x] 2.1 Error Logger modul implementálása
    - Létrehozni a `frontend/src/lib/errors/logger.ts` fájlt
    - Implementálni az init(), log(), warn(), info() metódusokat
    - Implementálni a logApiError() metódust
    - Integrálni a Rate Limiter-t és Retry Queue-t
    - _Követelmények: 7.1, 7.2, 7.3, 2.1, 2.2, 2.3_

  - [x] 2.2 Property teszt írása a kontextus teljességhez
    - **Property 1: Hiba Kontextus Teljessége**
    - **Validálja: Követelmények 3.1, 3.2, 3.3**

  - [x] 2.3 Property teszt írása a stack trace megőrzéshez
    - **Property 2: Stack Trace Megőrzése**
    - **Validálja: Követelmények 1.3**

  - [x] 2.4 Property teszt írása az API hiba információhoz
    - **Property 3: API Hiba Információ Teljessége**
    - **Validálja: Követelmények 2.1, 2.3**

  - [x] 2.5 Property teszt írása a manuális naplózáshoz
    - **Property 9: Manuális Naplózás Paraméter Megőrzése**
    - **Validálja: Követelmények 7.2, 7.3**

  - [x] 2.6 Property teszt írása a konfigurációhoz
    - **Property 10: Konfiguráció Hatékonysága**
    - **Validálja: Követelmények 8.1, 8.3**

- [x] 3. Globális hibakezelők
  - [x] 3.1 Globális error handler-ek implementálása
    - Létrehozni a `frontend/src/lib/errors/handlers.ts` fájlt
    - Implementálni a window.onerror kezelőt
    - Implementálni a window.onunhandledrejection kezelőt
    - _Követelmények: 1.1, 1.2, 1.4_

  - [x] 3.2 Error Logger inicializálás a root layout-ban
    - Módosítani a `frontend/src/routes/+layout.svelte` fájlt
    - Inicializálni az Error Logger-t az alkalmazás indulásakor
    - _Követelmények: 8.1, 8.2, 8.4_

- [x] 4. Ellenőrzési pont
  - Ellenőrizni, hogy minden frontend teszt átmegy
  - Kérdezni a felhasználót, ha kérdések merülnek fel

- [x] 5. API kliens integráció
  - [x] 5.1 API kliens módosítása hibanaplózáshoz
    - Módosítani a `frontend/src/lib/api/client.ts` fájlt
    - Integrálni az Error Logger-t a hibás válaszok kezelésébe
    - _Követelmények: 2.1, 2.2, 2.3_

  - [x] 5.2 Error Logger modul exportálása
    - Létrehozni a `frontend/src/lib/errors/index.ts` fájlt
    - Exportálni a publikus API-t
    - _Követelmények: 7.1_

- [x] 6. Backend Error Logger implementálása
  - [x] 6.1 ErrorLogger PHP osztály létrehozása
    - Létrehozni a `backend/src/ErrorLogger.php` fájlt
    - Implementálni a log(), getErrors(), getError() metódusokat
    - Implementálni a validációt és JSON fájl tárolást
    - _Követelmények: 5.1, 5.2, 5.3, 5.4_

  - [x] 6.2 Property teszt írása a backend validációhoz
    - **Property 6: Backend Validáció Helyessége**
    - **Validálja: Követelmények 5.1**

  - [x] 6.3 Property teszt írása a backend tároláshoz
    - **Property 7: Backend Tárolás Round-Trip**
    - **Validálja: Követelmények 5.2, 5.3**

  - [x] 6.4 Backend API endpoint létrehozása
    - Módosítani a `backend/public/index.php` fájlt
    - Hozzáadni a /api/errors POST és GET endpoint-okat
    - _Követelmények: 5.1, 5.4, 6.1_

- [x] 7. Ellenőrzési pont
  - Ellenőrizni, hogy minden backend teszt átmegy
  - Kérdezni a felhasználót, ha kérdések merülnek fel

- [x] 8. Hiba nézegető admin felület
  - [x] 8.1 Hiba lista oldal létrehozása
    - Létrehozni a `frontend/src/routes/(protected)/admin/errors/+page.svelte` fájlt
    - Implementálni a hibák listázását időrendi sorrendben
    - Implementálni a típus és dátum szűrőket
    - _Követelmények: 6.1, 6.2, 6.4, 6.5_

  - [x] 8.2 Property teszt írása a szűréshez
    - **Property 8: Szűrés Helyessége**
    - **Validálja: Követelmények 6.4, 6.5**

  - [x] 8.3 Hiba részletek megjelenítése
    - Implementálni a hiba kiválasztás és részletek megjelenítését
    - Megjeleníteni a stack trace-t és kontextust
    - _Követelmények: 6.3_

- [x] 9. Végső ellenőrzési pont
  - Ellenőrizni, hogy minden teszt átmegy
  - Kérdezni a felhasználót, ha kérdések merülnek fel

## Megjegyzések

- A `*` jelöléssel ellátott feladatok opcionálisak és kihagyhatók a gyorsabb MVP érdekében
- Minden feladat hivatkozik a specifikus követelményekre a nyomon követhetőség érdekében
- Az ellenőrzési pontok biztosítják az inkrementális validációt
- A property tesztek univerzális helyességi tulajdonságokat validálnak
- A unit tesztek specifikus példákat és edge case-eket validálnak
