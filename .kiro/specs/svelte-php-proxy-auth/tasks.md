# Implementációs Terv: Svelte 5 + PHP Proxy Auth Rendszer

## Áttekintés

Ez a dokumentum a Svelte 5 frontend és PHP proxy backend implementációs feladatait tartalmazza. A feladatok inkrementálisan épülnek egymásra, és minden lépés után működő kódot eredményeznek.

## Feladatok

- [x] 1. Projekt alapstruktúra létrehozása
  - [x] 1.1 Svelte 5 projekt inicializálása SvelteKit-tel és adapter-static-kal
    - SvelteKit projekt létrehozása
    - adapter-static telepítése és konfigurálása
    - SSG mód beállítása a gyökér layout-ban
    - _Követelmények: 1.1, 1.2, 1.4_
  - [x] 1.2 PHP backend projekt struktúra létrehozása
    - Mappa struktúra kialakítása (config/, src/, public/)
    - composer.json létrehozása vlucas/phpdotenv függőséggel
    - .env.example fájl létrehozása
    - _Követelmények: 2.2, 2.3_
  - [x] 1.3 PHP bootstrap és Config osztály implementálása
    - config/bootstrap.php létrehozása
    - .env betöltés és validáció
    - Config osztály statikus metódusokkal
    - _Követelmények: 2.3_

- [x] 2. PHP Proxy Backend alapok
  - [x] 2.1 Session kezelés implementálása
    - Session.php osztály létrehozása
    - Biztonságos session konfiguráció (httponly, secure, samesite)
    - Session indítás és kezelés
    - _Követelmények: 8.1_
  - [x] 2.2 Response osztály implementálása
    - JSON válasz formázás
    - Hiba válaszok kezelése
    - Sikeres válaszok kezelése
    - _Követelmények: 7.3, 7.4_
  - [x] 2.3 CSRF védelem implementálása
    - CSRF token generálás
    - Token validáció állapotváltoztató kéréseknél
    - _Követelmények: 8.5_
  - [x] 2.4 Property teszt: CSRF token validáció
    - **Tulajdonság 8: CSRF Token Validáció**
    - **Validálja: Követelmények 8.5**

- [x] 3. Token kezelés és API továbbítás
  - [x] 3.1 TokenHandler osztály implementálása
    - Token tárolás session-ben
    - Token visszaolvasás
    - Token törlés
    - _Követelmények: 2.2, 3.2, 3.4_
  - [x] 3.2 Property teszt: Session token tárolás körforgás
    - **Tulajdonság 2: Session Token Tárolás Körforgás**
    - **Validálja: Követelmények 3.2**
  - [x] 3.3 RequestForwarder osztály implementálása
    - cURL alapú HTTP kérések
    - Bearer token csatolás
    - Minden HTTP metódus támogatása (GET, POST, PUT, DELETE, PATCH)
    - _Követelmények: 2.1, 2.4, 2.5_
  - [x] 3.4 Fájlfeltöltés támogatás a RequestForwarder-ben
    - Multipart form-data kezelés
    - Fájl metaadatok megőrzése
    - _Követelmények: 2.6_
  - [x] 3.5 Property teszt: Fájlfeltöltés továbbítás
    - **Tulajdonság 10: Fájlfeltöltés Továbbítás**
    - **Validálja: Követelmények 2.6, 7.5**

- [x] 4. Automatikus token megújítás
  - [x] 4.1 Token megújítás logika implementálása
    - 401 válasz detektálás
    - Refresh token használata
    - Eredeti kérés újrapróbálása
    - Session frissítés sikeres megújítás után
    - _Követelmények: 4.1, 4.2, 4.3_
  - [x] 4.2 Sikertelen megújítás kezelése
    - Session törlés
    - 401 válasz visszaadása
    - _Követelmények: 4.4_
  - [x] 4.3 Property teszt: Automatikus token megújítás
    - **Tulajdonság 3: Automatikus Token Megújítás**
    - **Validálja: Követelmények 4.1, 4.2**

- [x] 5. PHP Proxy fő router
  - [x] 5.1 index.php router implementálása
    - Útvonal feldolgozás
    - HTTP metódus kezelés
    - Auth végpontok (/auth/login, /auth/logout, /auth/me, /auth/status)
    - Proxy végpont (minden más kérés továbbítása)
    - _Követelmények: 3.1, 3.3, 3.4_
  - [x] 5.2 Token biztonság implementálása
    - Tokenek kiszűrése a válaszokból
    - Csak user adatok visszaadása a frontend felé
    - _Követelmények: 3.3, 10.1_
  - [x] 5.3 Property teszt: Token biztonság
    - **Tulajdonság 1: Token Biztonság**
    - **Validálja: Követelmények 10.1**
  - [x] 5.4 Rate limiting implementálása
    - Kérés számláló session-ben
    - Limit ellenőrzés auth végpontoknál
    - _Követelmények: 10.5_
  - [x] 5.5 .htaccess fájlok létrehozása
    - API routing szabályok
    - CORS headers
    - _Követelmények: 2.1_

- [x] 6. Ellenőrzőpont - PHP Backend
  - Minden PHP teszt futtatása
  - Kérdések esetén egyeztetés a felhasználóval

- [x] 7. Svelte Frontend Auth Store
  - [x] 7.1 Auth store implementálása Svelte 5 runes-szal
    - AuthState típus definíció
    - $state használata az állapothoz
    - $derived használata számított értékekhez (isAdmin, hasPermission)
    - _Követelmények: 3.5, 6.3_
  - [x] 7.2 Auth műveletek implementálása
    - login() - bejelentkezés
    - logout() - kijelentkezés
    - checkAuth() - állapot ellenőrzés
    - _Követelmények: 3.1, 3.4, 8.3, 8.4_
  - [x] 7.3 Unit teszt: Auth store műveletek
    - Bejelentkezési állapot változás
    - Jogosultság ellenőrzés
    - Kijelentkezés

- [x] 8. Svelte Frontend API Kliens
  - [x] 8.1 API kliens implementálása
    - Egységes fetch wrapper
    - Minden HTTP metódus támogatása
    - CSRF token kezelés
    - _Követelmények: 7.1, 7.2_
  - [x] 8.2 Hibakezelés implementálása
    - 401 kezelés (átirányítás login-ra)
    - Hibaüzenetek feldolgozása
    - _Követelmények: 7.3, 7.4, 4.5_
  - [x] 8.3 Fájlfeltöltés támogatás
    - FormData kezelés
    - Progress tracking (opcionális)
    - _Követelmények: 7.5_
  - [x] 8.4 Property teszt: API kliens egységesség
    - **Tulajdonság 7: API Kliens Egységesség**
    - **Validálja: Követelmények 7.1**

- [x] 9. Route védelem és jogosultságok
  - [x] 9.1 Route guard implementálása
    - Védett útvonalak ellenőrzése
    - Átirányítás login oldalra
    - Redirect URL megőrzése
    - _Követelmények: 5.2, 5.5_
  - [x] 9.2 Property teszt: Védett útvonal átirányítás
    - **Tulajdonság 4: Védett Útvonal Átirányítás**
    - **Validálja: Követelmények 5.2, 5.5**
  - [x] 9.3 Hierarchikus route védelem implementálása
    - Route csoportok használata (protected), (admin)
    - Layout-alapú védelem
    - _Követelmények: 5.1, 5.4_
  - [x] 9.4 Property teszt: Hierarchikus route védelem
    - **Tulajdonság 5: Hierarchikus Route Védelem**
    - **Validálja: Követelmények 5.1, 5.4**
  - [x] 9.5 PermissionGate komponens implementálása
    - Jogosultság-alapú UI megjelenítés
    - Elemek elrejtése/letiltása
    - _Követelmények: 6.3, 6.4, 6.5_
  - [x] 9.6 Property teszt: Jogosultság-alapú UI
    - **Tulajdonság 6: Jogosultság-alapú UI Megjelenítés**
    - **Validálja: Követelmények 6.3, 6.5**

- [x] 10. Svelte oldalak és layoutok
  - [x] 10.1 Gyökér layout és SSG konfiguráció
    - +layout.svelte létrehozása
    - +layout.js SSG beállítások
    - Auth állapot inicializálás
    - _Követelmények: 1.1, 8.4_
  - [x] 10.2 Login oldal implementálása
    - Bejelentkezési űrlap
    - Hibakezelés
    - Redirect kezelés sikeres login után
    - _Követelmények: 3.1_
  - [x] 10.3 Védett layout implementálása
    - (protected)/+layout.svelte
    - Auth guard integráció
    - _Követelmények: 5.1, 5.2_
  - [x] 10.4 Admin layout implementálása
    - (admin)/+layout.svelte
    - Admin jogosultság ellenőrzés
    - _Követelmények: 5.1, 6.4_
  - [x] 10.5 Példa oldalak létrehozása
    - Főoldal (publikus)
    - Dashboard (védett)
    - Admin oldalak (admin jogosultság)
    - _Követelmények: 5.3_

- [x] 11. Session lejárat kezelés
  - [x] 11.1 Session lejárat detektálás
    - PHP oldalon session timeout ellenőrzés
    - 401 válasz lejárt session esetén
    - _Követelmények: 8.2_
  - [x] 11.2 Frontend session lejárat kezelés
    - 401 válasz kezelése
    - Átirányítás login oldalra
    - _Követelmények: 4.5_
  - [x] 11.3 Property teszt: Session lejárat kezelés
    - **Tulajdonság 9: Session Lejárat Kezelés**
    - **Validálja: Követelmények 4.4, 4.5, 8.2**

- [x] 12. Ellenőrzőpont - Teljes rendszer
  - Minden teszt futtatása (PHP + Frontend)
  - Build teszt (npm run build)
  - Kérdések esetén egyeztetés a felhasználóval

- [x] 13. Apache konfiguráció és dokumentáció
  - [x] 13.1 Apache Virtual Host konfiguráció minta
    - SSL beállítások
    - Document root konfiguráció
    - Titkos mappák védelme
    - _Követelmények: 10.3_
  - [x] 13.2 Telepítési dokumentáció
    - Build és deploy lépések
    - .env konfiguráció
    - Szerver követelmények

## Megjegyzések

- A `*` jelöléssel ellátott feladatok opcionálisak (tesztek)
- Minden feladat hivatkozik a kapcsolódó követelményekre
- A property tesztek a design dokumentumban definiált tulajdonságokat validálják
- Az ellenőrzőpontok lehetőséget adnak a felhasználóval való egyeztetésre
