# Követelmények Dokumentum

## Bevezetés

Ez a dokumentum egy modern webalkalmazás rendszer követelményeit írja le, amely Svelte 5 (SvelteKit SSG módban) frontendet és PHP proxy backendet kombinál. A rendszer célja, hogy biztonságosan kommunikáljon egy külső API backend rendszerrel, miközben Apache + PHP környezetben fut, JavaScript runtime nélkül. A rendszer JWT alapú autentikációt valósít meg, titkos adatokat kezel, és támogatja a jogosultság-alapú hozzáférés-vezérlést.

## Szójegyzék

- **Frontend**: A Svelte 5 alapú, SSG módban előállított statikus webalkalmazás
- **PHP_Proxy**: A PHP alapú proxy backend, amely közvetít a Frontend és a Külső_API között
- **Külső_API**: A külső API backend rendszer, amely a tényleges üzleti logikát és adatkezelést végzi
- **Access_Token**: Rövid élettartamú JWT token az API hívások autentikációjához
- **Refresh_Token**: Hosszabb élettartamú JWT token az Access_Token megújításához
- **Védett_Útvonal**: Olyan útvonal, amely csak autentikált felhasználók számára érhető el
- **Publikus_Útvonal**: Olyan útvonal, amely autentikáció nélkül is elérhető
- **Jogosultság**: Engedély, amely meghatározza, hogy a felhasználó milyen műveleteket végezhet
- **Munkamenet**: A PHP_Proxy által kezelt session, amely tárolja a tokeneket

## Követelmények

### 1. Követelmény: Svelte 5 Frontend SSG Konfiguráció

**Felhasználói történet:** Fejlesztőként szeretném, ha a Svelte 5 frontend SSG módban működne, hogy Apache + PHP környezetben telepíthető legyen JavaScript runtime nélkül.

#### Elfogadási kritériumok

1. A Frontend KELL, HOGY SvelteKit-et használjon static adapter-rel SSG build-hez
2. AMIKOR a Frontend build-elésre kerül, A build folyamat KELL, HOGY statikus HTML, CSS és JavaScript fájlokat generáljon
3. A Frontend KELL, HOGY telepíthető legyen bármely Apache szerverre Node.js runtime nélkül
4. A Frontend KELL, HOGY Svelte 5 runes szintaxist használjon a reaktivitáshoz

### 2. Követelmény: PHP Proxy Backend Alapstruktúra

**Felhasználói történet:** Fejlesztőként szeretnék egy PHP proxy backendet, amely közvetít a frontend és a külső API között, és biztonságosan kezeli a titkos adatokat.

#### Elfogadási kritériumok

1. A PHP_Proxy KELL, HOGY fogadja a Frontend kéréseit és továbbítsa azokat a Külső_API felé
2. A PHP_Proxy KELL, HOGY tárolja az Access_Token-t és Refresh_Token-t PHP session-ben
3. A PHP_Proxy KELL, HOGY kezelje a titkos adatokat (titkosítási kulcsok, rendszer azonosítók) kizárólag szerver oldalon
4. AMIKOR a Frontend kérést küld, A PHP_Proxy KELL, HOGY csatolja az Access_Token-t Bearer tokenként a Külső_API kérésekhez
5. A PHP_Proxy KELL, HOGY támogassa a GET, POST, PUT, DELETE és PATCH HTTP metódusokat
6. AMIKOR a Frontend fájlokat tölt fel, A PHP_Proxy KELL, HOGY továbbítsa a fájlokat a Külső_API felé

### 3. Követelmény: JWT Autentikáció

**Felhasználói történet:** Felhasználóként szeretnék biztonságosan bejelentkezni, hogy hozzáférhessek a védett tartalmakhoz.

#### Elfogadási kritériumok

1. AMIKOR egy felhasználó bejelentkezési adatokat küld, A PHP_Proxy KELL, HOGY továbbítsa azokat a Külső_API autentikációs végpontjára
2. AMIKOR a Külső_API tokeneket ad vissza, A PHP_Proxy KELL, HOGY tárolja az Access_Token-t és Refresh_Token-t a PHP session-ben
3. AMIKOR a Külső_API tokeneket ad vissza, A PHP_Proxy KELL, HOGY sikeres választ adjon a Frontendnek a tokenek felfedése nélkül
4. AMIKOR egy felhasználó kijelentkezik, A PHP_Proxy KELL, HOGY megsemmisítse a session-t és törölje az összes tárolt tokent
5. A Frontend KELL, HOGY nyomon kövesse az autentikációs állapotot a tényleges tokenek tárolása nélkül

### 4. Követelmény: Automatikus Token Megújítás

**Felhasználói történet:** Felhasználóként szeretném, ha a munkamenetem automatikusan megújulna, hogy ne kelljen újra bejelentkeznem rövid idő után.

#### Elfogadási kritériumok

1. AMIKOR a Külső_API 401 Unauthorized választ ad, A PHP_Proxy KELL, HOGY megpróbálja megújítani az Access_Token-t a Refresh_Token segítségével
2. AMIKOR a token megújítás sikeres, A PHP_Proxy KELL, HOGY újrapróbálja az eredeti kérést az új Access_Token-nel
3. AMIKOR a token megújítás sikeres, A PHP_Proxy KELL, HOGY frissítse a tárolt tokeneket a session-ben
4. HA a token megújítás sikertelen, AKKOR A PHP_Proxy KELL, HOGY 401-et adjon vissza a Frontendnek és törölje a session-t
5. AMIKOR a token megújítás sikertelen, A Frontend KELL, HOGY átirányítsa a felhasználót a bejelentkezési oldalra

### 5. Követelmény: Útvonal Védelem és Hierarchikus Útvonalkezelés

**Felhasználói történet:** Fejlesztőként szeretnék egy jól szervezett routing rendszert, ahol könnyen meghatározhatom, mely útvonalak védettek.

#### Elfogadási kritériumok

1. A Frontend KELL, HOGY támogassa a hierarchikus útvonal védelmet (pl. minden /admin/* alatti útvonal védett)
2. AMIKOR egy felhasználó Védett_Útvonalra navigál autentikáció nélkül, A Frontend KELL, HOGY átirányítsa a bejelentkezési oldalra
3. A Frontend KELL, HOGY támogassa az útvonal konfigurációt, amely skálázható 50+ oldalra
4. AMIKOR útvonalakat definiálunk, A Frontend KELL, HOGY lehetővé tegye teljes útvonal prefixek védettként jelölését
5. A Frontend KELL, HOGY megőrizze a célzott URL-t a bejelentkezési átirányítás során

### 6. Követelmény: Jogosultság-alapú Hozzáférés-vezérlés

**Felhasználói történet:** Adminisztrátorként szeretném, ha a felhasználók csak azokhoz a funkciókhoz férhetnének hozzá, amelyekhez jogosultságuk van.

#### Elfogadási kritériumok

1. AMIKOR egy felhasználó autentikál, A PHP_Proxy KELL, HOGY lekérje a felhasználói jogosultságokat a Külső_API-tól
2. A PHP_Proxy KELL, HOGY biztonságos módon adja vissza a felhasználói jogosultságokat a Frontendnek
3. A Frontend KELL, HOGY feltételesen jelenítse meg a UI elemeket a felhasználói jogosultságok alapján
4. AMIKOR egy felhasználó jogosultság-korlátozott funkcióhoz próbál hozzáférni, A Frontend KELL, HOGY ellenőrizze a jogosultságokat a kérés elküldése előtt
5. HA egy felhasználónak nincs meg a szükséges jogosultsága, AKKOR A Frontend KELL, HOGY elrejtse vagy letiltsa a megfelelő UI elemet

### 7. Követelmény: API Kommunikáció

**Felhasználói történet:** Fejlesztőként szeretnék egy egységes módszert az API hívások kezelésére a frontendben.

#### Elfogadási kritériumok

1. A Frontend KELL, HOGY biztosítson egy egységes API klienst minden HTTP metódushoz (GET, POST, PUT, DELETE, PATCH)
2. AMIKOR API kéréseket küldünk, A Frontend KELL, HOGY minden kérést a PHP_Proxy-n keresztül irányítson
3. A Frontend KELL, HOGY kecsesen kezelje az API hibákat és megfelelő üzeneteket jelenítsen meg
4. AMIKOR a PHP_Proxy hibát ad vissza, A Frontend KELL, HOGY feldolgozza és megjelenítse a hibaüzenetet
5. A Frontend KELL, HOGY támogassa a fájlfeltöltést a PHP_Proxy-n keresztül a Külső_API felé

### 8. Követelmény: Munkamenet Kezelés

**Felhasználói történet:** Felhasználóként szeretném, ha a bejelentkezési állapotom biztonságosan lenne kezelve.

#### Elfogadási kritériumok

1. A PHP_Proxy KELL, HOGY biztonságos PHP session konfigurációt használjon (httponly, secure flag-ek)
2. AMIKOR egy session lejár, A PHP_Proxy KELL, HOGY 401-et adjon vissza a Frontendnek
3. A Frontend KELL, HOGY biztosítson egy módszert az autentikációs állapot ellenőrzésére
4. AMIKOR a Frontend betöltődik, A Frontend KELL, HOGY ellenőrizze az autentikációs állapotot a PHP_Proxy-val
5. A PHP_Proxy KELL, HOGY CSRF védelmet implementáljon az állapotváltoztató kérésekhez

### 9. Követelmény: Külső API Végpontok Tervezése

**Felhasználói történet:** Fejlesztőként szeretném tudni, milyen végpontokra van szükség a külső API-ban a rendszer működéséhez.

#### Elfogadási kritériumok

1. A Külső_API KELL, HOGY biztosítson POST /auth/login végpontot az autentikációhoz
2. A Külső_API KELL, HOGY biztosítson POST /auth/refresh végpontot a token megújításhoz
3. A Külső_API KELL, HOGY biztosítson POST /auth/logout végpontot a kijelentkezéshez
4. A Külső_API KELL, HOGY biztosítson GET /auth/me végpontot az aktuális felhasználói adatokhoz és jogosultságokhoz
5. A Külső_API KELL, HOGY 401 Unauthorized választ adjon védett végpontokra érvényes Bearer token nélkül
6. A Külső_API KELL, HOGY tartalmazza a felhasználói jogosultságokat a /auth/me válaszban

### 10. Követelmény: Biztonság

**Felhasználói történet:** Biztonsági szakértőként szeretném, ha a rendszer megfelelne a biztonsági követelményeknek.

#### Elfogadási kritériumok

1. A PHP_Proxy SOHA NEM SZABAD, HOGY felfedje az Access_Token-t vagy Refresh_Token-t a Frontend felé
2. A PHP_Proxy KELL, HOGY validálja és szanitizálja az összes bejövő kérést
3. A PHP_Proxy KELL, HOGY HTTPS-t használjon minden Külső_API kommunikációhoz
4. A Frontend KELL, HOGY XSS védelmi intézkedéseket implementáljon
5. A PHP_Proxy KELL, HOGY rate limiting-et implementáljon az autentikációs végpontokhoz
