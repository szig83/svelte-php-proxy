# Requirements Document

## Introduction

Ez a dokumentum a frontend hibanaplózási rendszer követelményeit tartalmazza. A cél egy olyan megoldás létrehozása, amely a SvelteKit frontend alkalmazásban keletkező hibákat (JavaScript hibák, API hibák, kezeletlen kivételek) összegyűjti és egy backend szolgáltatáson keresztül perzisztens módon tárolja, hogy azok később visszanézhetők és elemezhetők legyenek.

## Glossary

- **Error_Logger**: A frontend modul, amely a hibák összegyűjtéséért és továbbításáért felelős
- **Error_Entry**: Egy naplózott hiba rekord, amely tartalmazza a hiba részleteit és kontextusát
- **Error_Backend**: A PHP backend endpoint, amely fogadja és tárolja a hibajelentéseket
- **Error_Store**: A perzisztens tárhely (fájl vagy adatbázis), ahol a hibák tárolódnak
- **Error_Context**: A hiba környezeti információi (URL, user agent, auth állapot, stb.)
- **Error_Viewer**: Az admin felület a hibák megtekintéséhez

## Requirements

### Requirement 1: Globális hibakezelés

**User Story:** Fejlesztőként szeretném, ha az alkalmazásban keletkező kezeletlen JavaScript hibák automatikusan naplózásra kerülnének, hogy ne maradjanak észrevétlenül.

#### Acceptance Criteria

1. WHEN egy kezeletlen JavaScript hiba keletkezik, THE Error_Logger SHALL elkapja és naplózza a hibát
2. WHEN egy kezeletlen Promise rejection történik, THE Error_Logger SHALL elkapja és naplózza a hibát
3. WHEN egy hiba naplózásra kerül, THE Error_Logger SHALL összegyűjti a stack trace-t, ha elérhető
4. WHEN egy hiba naplózásra kerül, THE Error_Logger SHALL nem szakítja meg az alkalmazás működését

### Requirement 2: API hiba naplózás

**User Story:** Fejlesztőként szeretném, ha az API hívások során keletkező hibák automatikusan naplózásra kerülnének a hibakeresés megkönnyítése érdekében.

#### Acceptance Criteria

1. WHEN az API kliens hibás választ kap, THE Error_Logger SHALL naplózza a hibát az endpoint és a hibakód megjelölésével
2. WHEN hálózati hiba történik, THE Error_Logger SHALL naplózza a hibát a hálózati hiba típusával
3. WHEN egy API hiba naplózásra kerül, THE Error_Logger SHALL tartalmazza a request URL-t és a HTTP státuszkódot

### Requirement 3: Hiba kontextus gyűjtés

**User Story:** Fejlesztőként szeretném, ha a naplózott hibák mellé kontextus információk is tárolódnának, hogy könnyebben reprodukálhassam a problémákat.

#### Acceptance Criteria

1. WHEN egy hiba naplózásra kerül, THE Error_Logger SHALL hozzáadja az aktuális oldal URL-jét
2. WHEN egy hiba naplózásra kerül, THE Error_Logger SHALL hozzáadja a user agent információt
3. WHEN egy hiba naplózásra kerül, THE Error_Logger SHALL hozzáadja az időbélyeget
4. WHEN egy hiba naplózásra kerül és a felhasználó be van jelentkezve, THE Error_Logger SHALL hozzáadja a felhasználó azonosítóját (ha elérhető)
5. WHEN egy hiba naplózásra kerül, THE Error_Logger SHALL hozzáadja az alkalmazás verzióját (ha konfigurálva van)

### Requirement 4: Hiba küldés a backendre

**User Story:** Fejlesztőként szeretném, ha a hibák egy backend szolgáltatásba kerülnének elküldésre, hogy perzisztensen tárolódjanak.

#### Acceptance Criteria

1. WHEN egy hiba naplózásra kerül, THE Error_Logger SHALL elküldi a hibát a backend API-nak
2. WHEN a hiba küldés sikertelen, THE Error_Logger SHALL megpróbálja később újraküldeni (retry mechanizmus)
3. WHEN túl sok hiba keletkezik rövid időn belül, THE Error_Logger SHALL korlátozza a küldések számát (rate limiting)
4. WHEN a hiba küldés sikertelen és a retry is sikertelen, THE Error_Logger SHALL tárolja a hibát lokálisan (localStorage)

### Requirement 5: Backend hiba fogadás és tárolás

**User Story:** Fejlesztőként szeretném, ha a backend fogadná és tárolná a frontend hibákat, hogy később visszanézhetők legyenek.

#### Acceptance Criteria

1. WHEN a backend hibát fogad, THE Error_Backend SHALL validálja a bejövő adatokat
2. WHEN a validáció sikeres, THE Error_Backend SHALL tárolja a hibát az Error_Store-ban
3. WHEN a hiba tárolásra kerül, THE Error_Backend SHALL egyedi azonosítót rendel hozzá
4. WHEN a tárolás sikeres, THE Error_Backend SHALL visszajelzést küld a frontendnek

### Requirement 6: Hiba megtekintés

**User Story:** Fejlesztőként szeretném megtekinteni a naplózott hibákat egy egyszerű felületen, hogy áttekintsem a problémákat.

#### Acceptance Criteria

1. WHEN egy admin felhasználó megnyitja a hiba nézegető oldalt, THE Error_Viewer SHALL megjeleníti a hibák listáját időrendi sorrendben
2. WHEN a hibák listája megjelenik, THE Error_Viewer SHALL mutatja a hiba típusát, üzenetét és időpontját
3. WHEN egy felhasználó kiválaszt egy hibát, THE Error_Viewer SHALL megjeleníti a hiba teljes részleteit (stack trace, kontextus)
4. WHEN a hibák listája megjelenik, THE Error_Viewer SHALL lehetővé teszi a szűrést hiba típus szerint
5. WHEN a hibák listája megjelenik, THE Error_Viewer SHALL lehetővé teszi a szűrést dátum tartomány szerint

### Requirement 7: Manuális hiba naplózás

**User Story:** Fejlesztőként szeretnék manuálisan is naplózni hibákat a kódból, hogy a kezelt hibákat is nyomon követhessem.

#### Acceptance Criteria

1. THE Error_Logger SHALL biztosít egy API-t manuális hiba naplózáshoz
2. WHEN manuális hiba naplózás történik, THE Error_Logger SHALL lehetővé teszi egyedi kontextus hozzáadását
3. WHEN manuális hiba naplózás történik, THE Error_Logger SHALL támogatja a különböző súlyossági szinteket (error, warning, info)

### Requirement 8: Konfiguráció

**User Story:** Fejlesztőként szeretném konfigurálni a hibanaplózás viselkedését, hogy a különböző környezetekhez igazíthassam.

#### Acceptance Criteria

1. THE Error_Logger SHALL lehetővé teszi a naplózás ki/bekapcsolását környezeti változóval
2. THE Error_Logger SHALL lehetővé teszi a backend endpoint URL konfigurálását
3. THE Error_Logger SHALL lehetővé teszi a rate limit beállítását
4. WHEN development módban van az alkalmazás, THE Error_Logger SHALL konzolra is kiírja a hibákat
