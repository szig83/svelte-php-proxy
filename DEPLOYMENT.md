# Telepítési Dokumentáció

## Svelte 5 + PHP Proxy Auth Rendszer

Ez a dokumentum a rendszer telepítésének lépéseit írja le Apache + PHP környezetben.

## Tartalomjegyzék

1. [Szerver Követelmények](#szerver-követelmények)
2. [Projekt Struktúra](#projekt-struktúra)
3. [Build Folyamat](#build-folyamat)
4. [Deploy Lépések](#deploy-lépések)
5. [Környezeti Változók](#környezeti-változók)
6. [Apache Konfiguráció](#apache-konfiguráció)
7. [SSL Tanúsítvány](#ssl-tanúsítvány)
8. [Hibaelhárítás](#hibaelhárítás)

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

```bash
cd frontend

# Függőségek telepítése
npm install
# vagy
bun install

# Production build
npm run build
# vagy
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

```bash
# Lokálisan
cd frontend
npm run build

# Feltöltés
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
