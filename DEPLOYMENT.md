# Telepítési Dokumentáció

## Svelte 5 + PHP Proxy Auth Rendszer

Ez a dokumentum a rendszer telepítésének lépéseit írja le Apache + PHP környezetben.

## Tartalomjegyzék

1. [Fejlesztési Környezet](#fejlesztési-környezet)
2. [Szerver Követelmények](#szerver-követelmények)
3. [Projekt Struktúra](#projekt-struktúra)
4. [Build Folyamat](#build-folyamat)
5. [Deploy Lépések](#deploy-lépések)
6. [Deploy Különböző OS-ekről](#deploy-különböző-os-ekről)
7. [Környezeti Változók](#környezeti-változók)
8. [Apache Konfiguráció](#apache-konfiguráció)
9. [SSL Tanúsítvány](#ssl-tanúsítvány)
10. [Hibaelhárítás](#hibaelhárítás)

---

## Fejlesztési Környezet

A napi fejlesztés során **NEM kell** minden változtatás után buildelni és szinkronizálni. A fejlesztés lokálisan történik, gyors visszajelzéssel.

### Előfeltételek (Lokális Gép)

| Komponens | Verzió | Telepítés |
|-----------|--------|-----------|
| Node.js | 18+ | [nodejs.org](https://nodejs.org) |
| **VAGY** Bun | 1.0+ | [bun.sh](https://bun.sh) |
| PHP | 8.1+ | Homebrew (macOS), XAMPP/Laragon (Windows) |

**JavaScript Runtime választás**: Használhatsz Node.js-t vagy Bun-t - mindkettő tökéletesen működik. A Bun gyorsabb, de a Node.js elterjedtebb.

### Frontend Fejlesztés

A Svelte fejlesztői szerver HMR-rel (Hot Module Replacement) működik - a változtatások azonnal megjelennek a böngészőben.

#### Node.js + npm használatával

```bash
cd frontend

# Függőségek telepítése (első alkalommal)
npm install

# Fejlesztői szerver indítása
npm run dev

# Böngészőben: http://localhost:5173
```

#### Bun használatával

```bash
cd frontend

# Függőségek telepítése (első alkalommal)
bun install

# Fejlesztői szerver indítása
bun run dev

# Böngészőben: http://localhost:5173
```

**Előnyök**:
- Mentés után ~100ms-en belül frissül a böngésző
- Nem kell build, nem kell szinkronizálás
- CSS változtatások azonnal látszanak
- Komponens állapot megmarad frissítéskor

### Backend Fejlesztés (Lokális PHP)

A PHP-hoz nem kell build, de szükséged van egy lokális PHP szerverre.

#### macOS (Homebrew)

```bash
# PHP telepítése
brew install php

# PHP szerver indítása
cd backend/public
php -S localhost:8000

# API elérhető: http://localhost:8000
```

#### Windows (Laragon - Ajánlott)

1. Telepítsd a [Laragon](https://laragon.org/download/)-t
2. Másold a `backend` mappát a `C:\laragon\www\myapp-api` helyre
3. Indítsd el a Laragon-t
4. API elérhető: `http://myapp-api.test` vagy `http://localhost/myapp-api`

#### Windows (XAMPP)

1. Telepítsd a [XAMPP](https://www.apachefriends.org/)-ot
2. Másold a `backend/public` mappát a `C:\xampp\htdocs\api` helyre
3. Indítsd el az Apache-ot a XAMPP Control Panel-ből
4. API elérhető: `http://localhost/api`

### Frontend + Backend Összekapcsolása

A frontend API hívásait a lokális PHP-ra kell irányítani. Ehhez a Vite proxy-t használjuk.

#### vite.config.ts Beállítása

```typescript
// frontend/vite.config.ts
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [sveltekit()],
  server: {
    proxy: {
      // Lokális PHP szerver
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '')
      }
    }
  }
});
```

#### Távoli Backend Használata (Opcionális)

Ha van staging szervered, használhatod azt is fejlesztés közben:

```typescript
// frontend/vite.config.ts
export default defineConfig({
  plugins: [sveltekit()],
  server: {
    proxy: {
      '/api': {
        target: 'https://staging.myapp.com/api',
        changeOrigin: true,
        secure: true
      }
    }
  }
});
```

### Tipikus Fejlesztési Workflow

1. **Reggel**: `npm run dev` indítása a frontend mappában
2. **Fejlesztés közben**: Kód szerkesztése, mentés → automatikus frissülés
3. **API tesztelés**: Lokális PHP szerver vagy staging backend
4. **Nap végén**: Commit, push
5. **Deploy**: Csak amikor staging-re vagy production-be mész

### Mikor Kell Buildelni?

| Helyzet | Build szükséges? |
|---------|------------------|
| CSS módosítás | ❌ Nem |
| Új komponens | ❌ Nem |
| Új oldal | ❌ Nem |
| API kliens módosítás | ❌ Nem |
| Staging-re deploy | ✅ Igen |
| Production-be deploy | ✅ Igen |
| SSG specifikus teszt | ✅ Igen (`npm run preview`) |

---

## Szerver Követelmények

### Minimum Követelmények

| Komponens | Verzió | Megjegyzés |
|-----------|--------|------------|
| Apache | 2.4+ | mod_rewrite, mod_headers szükséges |
| PHP | 8.1+ | cURL, JSON, Session kiterjesztések |
| Node.js | 18+ | Csak build-hez szükséges |
| npm/bun | latest | Csak build-hez szükséges |

### Szükséges Apache Modulok

```bash
# Modulok engedélyezése
sudo a2enmod ssl
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod deflate
sudo a2enmod expires
sudo a2enmod proxy_fcgi  # PHP-FPM esetén

# Apache újraindítása
sudo systemctl restart apache2
```

### Szükséges PHP Kiterjesztések

```bash
# Ellenőrzés
php -m | grep -E "(curl|json|session|mbstring)"

# Telepítés (Ubuntu/Debian)
sudo apt install php8.1-curl php8.1-json php8.1-mbstring
```

---

## Projekt Struktúra

### Fejlesztési Struktúra

```
project/
├── frontend/           # Svelte 5 frontend
│   ├── src/
│   ├── static/
│   ├── package.json
│   └── svelte.config.js
├── backend/            # PHP proxy backend
│   ├── config/
│   ├── src/
│   ├── public/
│   └── composer.json
└── DEPLOYMENT.md
```

### Production Struktúra (Szerveren)

```
/var/www/myapp/                     # Alkalmazás gyökér (NEM publikus!)
├── .env                            # Környezeti változók (TITKOS!)
├── config/
│   └── bootstrap.php               # PHP bootstrap
├── src/                            # PHP forráskód
│   ├── Session.php
│   ├── TokenHandler.php
│   ├── RequestForwarder.php
│   ├── Response.php
│   ├── RateLimiter.php
│   ├── CsrfProtection.php
│   └── TokenRefresher.php
├── vendor/                         # Composer csomagok
└── public_html/                    # DOCUMENT ROOT (csak ez publikus!)
    ├── index.html                  # Svelte SPA belépési pont
    ├── _app/                       # Svelte build assets
    ├── api/                        # PHP Proxy végpont
    │   ├── index.php
    │   └── .htaccess
    └── .htaccess
```

**FONTOS**: A `.env`, `config/`, `src/` és `vendor/` mappák a document root-on KÍVÜL vannak!

---

## Build Folyamat

### 1. Frontend Build

#### Node.js + npm használatával

```bash
cd frontend

# Függőségek telepítése
npm install

# Production build
npm run build

# A build kimenet: frontend/build/
```

#### Bun használatával

```bash
cd frontend

# Függőségek telepítése
bun install

# Production build
bun run build

# A build kimenet: frontend/build/
```

### 2. Backend Előkészítés

```bash
cd backend

# Composer függőségek telepítése
composer install --no-dev --optimize-autoloader

# .env.example másolása (szerveren majd kitöltjük)
cp .env.example .env
```

---

## Deploy Lépések

### 1. Mappa Struktúra Létrehozása

```bash
# SSH kapcsolat a szerverhez
ssh user@server

# Alkalmazás mappa létrehozása
sudo mkdir -p /var/www/myapp/{config,src,public_html/api}
sudo chown -R www-data:www-data /var/www/myapp
sudo chmod -R 755 /var/www/myapp
```

### 2. Fájlok Feltöltése

```bash
# Lokális gépről

# Frontend build -> public_html/
rsync -avz --delete frontend/build/ user@server:/var/www/myapp/public_html/

# PHP src -> src/
rsync -avz backend/src/ user@server:/var/www/myapp/src/

# PHP public -> public_html/api/
rsync -avz backend/public/ user@server:/var/www/myapp/public_html/api/

# Config -> config/
rsync -avz backend/config/ user@server:/var/www/myapp/config/

# Vendor -> vendor/
rsync -avz backend/vendor/ user@server:/var/www/myapp/vendor/
```

### 3. Jogosultságok Beállítása

```bash
# Szerveren
sudo chown -R www-data:www-data /var/www/myapp
sudo chmod -R 755 /var/www/myapp
sudo chmod 600 /var/www/myapp/.env  # .env csak olvasható a tulajdonos számára
```

### 4. Apache Konfiguráció

```bash
# Virtual host másolása
sudo cp /var/www/myapp/config/apache-vhost.conf.example /etc/apache2/sites-available/myapp.conf

# Szerkesztés (domain, útvonalak módosítása)
sudo nano /etc/apache2/sites-available/myapp.conf

# Site engedélyezése
sudo a2ensite myapp.conf

# Konfiguráció tesztelése
sudo apache2ctl configtest

# Apache újratöltése
sudo systemctl reload apache2
```

---

## Deploy Különböző OS-ekről

### macOS

A macOS-en az `rsync` és `ssh` alapból elérhető.

#### Fájlok Feltöltése

```bash
# Frontend
rsync -avz --delete frontend/build/ user@server:/var/www/myapp/public_html/

# Backend
rsync -avz backend/src/ user@server:/var/www/myapp/src/
rsync -avz backend/public/ user@server:/var/www/myapp/public_html/api/
rsync -avz backend/config/ user@server:/var/www/myapp/config/
rsync -avz backend/vendor/ user@server:/var/www/myapp/vendor/
```

#### SSH Kulcs Beállítása (Ajánlott)

```bash
# SSH kulcs generálása (ha még nincs)
ssh-keygen -t ed25519 -C "your_email@example.com"

# Kulcs másolása a szerverre
ssh-copy-id user@server

# Ezután jelszó nélkül tudsz csatlakozni
ssh user@server
```

### Windows

Windows-on több lehetőség van:

#### 1. WSL (Windows Subsystem for Linux) - Ajánlott

A WSL-ben ugyanúgy működik minden, mint Linux-on.

```bash
# WSL telepítése (PowerShell Admin)
wsl --install

# WSL-ben
rsync -avz --delete frontend/build/ user@server:/var/www/myapp/public_html/
```

#### 2. Git Bash

A Git for Windows tartalmaz `rsync`-et és `ssh`-t.

```bash
# Git Bash-ben (ugyanaz mint Linux/macOS)
rsync -avz --delete frontend/build/ user@server:/var/www/myapp/public_html/
```

#### 3. PowerShell + SCP

Ha nincs rsync, használhatsz `scp`-t:

```powershell
# Frontend feltöltése
scp -r frontend/build/* user@server:/var/www/myapp/public_html/

# Backend feltöltése
scp -r backend/src/* user@server:/var/www/myapp/src/
scp -r backend/public/* user@server:/var/www/myapp/public_html/api/
```

**Megjegyzés**: Az `scp` nem törli a régi fájlokat, csak felülírja. Nagyobb változtatásoknál érdemes előbb törölni a távoli mappát.

#### 4. WinSCP (GUI)

Ha grafikus felületet preferálsz:

1. Telepítsd a [WinSCP](https://winscp.net/)-t
2. Csatlakozz a szerverhez (SFTP)
3. Húzd át a fájlokat a megfelelő mappákba

#### 5. VS Code SFTP Extension

1. Telepítsd a "SFTP" extension-t (Natizyskunk)
2. Konfiguráld a `.vscode/sftp.json` fájlt:

```json
{
    "name": "Production Server",
    "host": "server.example.com",
    "protocol": "sftp",
    "port": 22,
    "username": "user",
    "remotePath": "/var/www/myapp",
    "uploadOnSave": false,
    "privateKeyPath": "~/.ssh/id_ed25519"
}
```

3. Jobb klikk → "Upload" a fájlokon/mappákon

### Deploy Script (Cross-Platform)

Érdemes egy egyszerű deploy scriptet készíteni:

#### deploy.sh (macOS/Linux/WSL/Git Bash)

```bash
#!/bin/bash

SERVER="user@server"
REMOTE_PATH="/var/www/myapp"

echo "🔨 Building frontend..."
cd frontend

# Használj npm-et vagy bun-t
if command -v bun &> /dev/null; then
    bun run build
else
    npm run build
fi

cd ..

echo "📤 Uploading frontend..."
rsync -avz --delete frontend/build/ $SERVER:$REMOTE_PATH/public_html/

echo "📤 Uploading backend..."
rsync -avz backend/src/ $SERVER:$REMOTE_PATH/src/
rsync -avz backend/public/ $SERVER:$REMOTE_PATH/public_html/api/

echo "✅ Deploy complete!"
```

Használat:
```bash
chmod +x deploy.sh
./deploy.sh
```

#### deploy.ps1 (PowerShell)

```powershell
$SERVER = "user@server"
$REMOTE_PATH = "/var/www/myapp"

Write-Host "🔨 Building frontend..." -ForegroundColor Cyan
Set-Location frontend

# Használj npm-et vagy bun-t
if (Get-Command bun -ErrorAction SilentlyContinue) {
    bun run build
} else {
    npm run build
}

Set-Location ..

Write-Host "📤 Uploading frontend..." -ForegroundColor Cyan
scp -r frontend/build/* ${SERVER}:${REMOTE_PATH}/public_html/

Write-Host "📤 Uploading backend..." -ForegroundColor Cyan
scp -r backend/src/* ${SERVER}:${REMOTE_PATH}/src/
scp -r backend/public/* ${SERVER}:${REMOTE_PATH}/public_html/api/

Write-Host "✅ Deploy complete!" -ForegroundColor Green
```

Használat:
```powershell
.\deploy.ps1
```

---

## Környezeti Változók

### .env Fájl Létrehozása

```bash
# Szerveren
sudo nano /var/www/myapp/.env
```

### .env Tartalom

```env
# Külső API konfiguráció
EXTERNAL_API_URL=https://api.example.com
EXTERNAL_API_TIMEOUT=30

# Titkosítási kulcsok (generálj egyedi értékeket!)
ENCRYPTION_KEY=your-32-character-encryption-key-here
SYSTEM_ID=your-unique-system-identifier

# Session konfiguráció
SESSION_LIFETIME=3600
SESSION_NAME=myapp_session

# Rate limiting
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60

# Debug mód (PRODUCTION-BEN MINDIG false!)
DEBUG_MODE=false
```

### Titkos Kulcsok Generálása

```bash
# ENCRYPTION_KEY generálása (32 karakter)
openssl rand -base64 32 | head -c 32

# SYSTEM_ID generálása
uuidgen
```

### Fontos Biztonsági Szabályok

1. **SOHA ne commitold a `.env` fájlt** a verziókezelőbe
2. A `.env` fájl jogosultsága legyen `600` (csak tulajdonos olvashatja)
3. Production-ben a `DEBUG_MODE` mindig `false` legyen
4. Minden környezethez (dev, staging, prod) egyedi kulcsokat használj

---

## Apache Konfiguráció

### Virtual Host Beállítása

A részletes konfiguráció a `backend/config/apache-vhost.conf.example` fájlban található.

### Főbb Beállítások

1. **SSL/TLS**: Kötelező HTTPS használat
2. **Document Root**: `/var/www/myapp/public_html`
3. **SPA Routing**: `FallbackResource /index.html`
4. **Titkos mappák védelme**: config, src, vendor nem elérhetőek

### Konfiguráció Tesztelése

```bash
# Szintaxis ellenőrzés
sudo apache2ctl configtest

# Részletes teszt
sudo apache2ctl -t -D DUMP_VHOSTS
```

---

## SSL Tanúsítvány

### Let's Encrypt (Ajánlott)

```bash
# Certbot telepítése
sudo apt install certbot python3-certbot-apache

# Tanúsítvány beszerzése
sudo certbot --apache -d myapp.example.com

# Automatikus megújítás tesztelése
sudo certbot renew --dry-run
```

### Manuális Tanúsítvány

Ha saját tanúsítványt használsz, módosítsd a Virtual Host-ban:

```apache
SSLCertificateFile /path/to/your/certificate.crt
SSLCertificateKeyFile /path/to/your/private.key
SSLCertificateChainFile /path/to/your/chain.crt
```

---

## Hibaelhárítás

### Gyakori Hibák

#### 1. 500 Internal Server Error

```bash
# Apache error log ellenőrzése
sudo tail -f /var/log/apache2/myapp_error.log

# PHP error log
sudo tail -f /var/log/apache2/myapp_php_errors.log
```

#### 2. 403 Forbidden

- Ellenőrizd a fájl jogosultságokat
- Ellenőrizd az Apache `Require` direktívákat
- Győződj meg róla, hogy a `mod_rewrite` engedélyezve van

#### 3. Session Problémák

```bash
# Session mappa jogosultságok
sudo chown www-data:www-data /var/lib/php/sessions
sudo chmod 1733 /var/lib/php/sessions
```

#### 4. CORS Hibák

- Ellenőrizd a `.htaccess` CORS beállításait
- Production-ben cseréld a `*`-ot a konkrét domain-re

### Debug Mód

Fejlesztés/hibakeresés során ideiglenesen engedélyezheted:

```env
# .env
DEBUG_MODE=true
```

**FIGYELEM**: Production-ben SOHA ne hagyd bekapcsolva!

### Hasznos Parancsok

```bash
# Apache státusz
sudo systemctl status apache2

# Apache újraindítás
sudo systemctl restart apache2

# PHP verzió és modulok
php -v
php -m

# Composer függőségek frissítése
cd /var/www/myapp && composer install --no-dev

# Jogosultságok javítása
sudo chown -R www-data:www-data /var/www/myapp
```

---

## Frissítési Folyamat

### Frontend Frissítés

#### Node.js + npm

```bash
cd frontend
npm run build
rsync -avz --delete frontend/build/ user@server:/var/www/myapp/public_html/
```

#### Bun

```bash
cd frontend
bun run build
rsync -avz --delete frontend/build/ user@server:/var/www/myapp/public_html/
```

### Backend Frissítés

```bash
# PHP fájlok feltöltése
rsync -avz backend/src/ user@server:/var/www/myapp/src/
rsync -avz backend/public/ user@server:/var/www/myapp/public_html/api/

# Ha új Composer függőség van
rsync -avz backend/vendor/ user@server:/var/www/myapp/vendor/
```

### Zero-Downtime Deploy (Opcionális)

Nagyobb rendszereknél érdemes symlink-alapú deploy-t használni:

```bash
/var/www/myapp/
├── releases/
│   ├── 20240115_120000/
│   └── 20240116_140000/
├── current -> releases/20240116_140000/
└── shared/
    └── .env
```

---

## Biztonsági Ellenőrzőlista

- [ ] `.env` fájl jogosultsága `600`
- [ ] `DEBUG_MODE=false` production-ben
- [ ] SSL tanúsítvány érvényes és megújul automatikusan
- [ ] Titkos mappák (config, src, vendor) nem elérhetőek kívülről
- [ ] CORS beállítások a konkrét domain-re korlátozva
- [ ] Rate limiting engedélyezve
- [ ] Apache és PHP naprakész verzió
- [ ] Tűzfal szabályok beállítva (csak 80, 443 port nyitva)
