# Dinamikus Menü Rendszer

## Áttekintés

A dinamikus menü rendszer lehetővé teszi, hogy a menüstruktúra a backend API-ból töltődjön be, a felhasználó jogosultságai alapján. Ez rugalmas, karbantartható megoldást biztosít a navigációs menük kezelésére.

## Komponensek

### 1. DynamicMenu.svelte

A fő menü komponens, amely betölti és megjeleníti a menüelemeket.

**Props:**
- `type`: MenuType - A menü típusa ('protected', 'admin', stb.)
- `class`: string - Opcionális CSS osztályok

**Funkciók:**
- Automatikus menü betöltés a backend-ről
- Többszintű menü támogatás (nested children)
- Aktív menüpont kiemelés
- Betöltési és hibaállapot kezelés
- Újrapróbálás funkció hiba esetén

**Használat:**
```svelte
<DynamicMenu type="protected" />
<DynamicMenu type="admin" />
```

### 2. MenuIcon.svelte

Ikon komponens a menüelemekhez.

**Props:**
- `icon`: string - Az ikon azonosítója
- `size`: 'xs' | 'sm' | 'md' - Az ikon mérete

**Támogatott ikonok:**
- wallet, calculator, lock, document, gift, mail, card, users
- upload, file-upload, repeat, star, file-text, heart, x-circle
- dashboard, chart, bug, settings, cog, home, arrow-left, circle/dot

**Használat:**
```svelte
<MenuIcon icon="wallet" size="md" />
```

### 3. API Client (menu.ts)

**Típusok:**

```typescript
interface MenuItem {
  label: string;      // Menüpont szövege
  href: string;       // Hivatkozás URL
  icon: string;       // Ikon azonosító
  children?: MenuItem[]; // Opcionális almenü elemek
}

type MenuType = 'protected' | 'admin' | string;

interface MenuResponse {
  success: boolean;
  items?: MenuItem[];
  error?: {
    code: string;
    message: string;
  };
}
```

**Funkció:**

```typescript
async function fetchMenu(type: MenuType = 'protected'): Promise<MenuResponse>
```

## Backend Integráció

### PHP Proxy Végpont

A menü lekérés egy egyszerű forward a külső API felé, nincs külön kezelés.

**Útvonal:** `/api/menu`

**Metódus:** POST

**Request body:**
```json
{
  "type": "protected"
}
```

**Válasz formátum:**
```json
{
  "success": true,
  "items": [
    {
      "label": "Dashboard",
      "href": "/admin",
      "icon": "dashboard"
    },
    {
      "label": "Pénzügyek",
      "href": "/penzugyek",
      "icon": "document",
      "children": [
        {
          "label": "Folyószámla",
          "href": "/penzugyek/folyoszamla",
          "icon": "document"
        }
      ]
    }
  ]
}
```

### Külső API Végpont

**Útvonal:** `/menu`

**Metódus:** POST

**Request body:**
```json
{
  "type": "protected"
}
```

**Autentikáció:** Bearer token (access_token)

A külső API a felhasználó jogosultságai alapján (JWT token-ből kinyerve) visszaadja a megfelelő menüstruktúrát.

## Menü Struktúra

### Egyszintű menü

```json
{
  "success": true,
  "items": [
    {
      "label": "Főoldal",
      "href": "/",
      "icon": "home"
    },
    {
      "label": "Beállítások",
      "href": "/settings",
      "icon": "settings"
    }
  ]
}
```

### Többszintű menü (nested)

```json
{
  "success": true,
  "items": [
    {
      "label": "Pénzügyek",
      "href": "/penzugyek",
      "icon": "wallet",
      "children": [
        {
          "label": "Számlák",
          "href": "/penzugyek/szamlak",
          "icon": "document",
          "children": [
            {
              "label": "Bejövő",
              "href": "/penzugyek/szamlak/bejovo",
              "icon": "arrow-left"
            },
            {
              "label": "Kimenő",
              "href": "/penzugyek/szamlak/kimeno",
              "icon": "arrow-right"
            }
          ]
        }
      ]
    }
  ]
}
```

## Használat a Projektben

### Protected Layout (Sidebar.svelte)

```svelte
<script lang="ts">
  import DynamicMenu from './DynamicMenu.svelte';
  import { getIsAdmin } from '$lib/auth/store.svelte';
</script>

<aside>
  <DynamicMenu type="protected" />

  {#if getIsAdmin()}
    <!-- Admin link -->
  {/if}
</aside>
```

### Admin Layout (AdminSidebar.svelte)

```svelte
<script lang="ts">
  import DynamicMenu from '../DynamicMenu.svelte';
</script>

<aside>
  <DynamicMenu type="admin" />
</aside>
```

## Előnyök

1. **Központosított menükezelés**: A menüstruktúra a backend-en van definiálva
2. **Jogosultság alapú**: A felhasználó csak a számára elérhető menüpontokat látja
3. **Rugalmas**: Könnyen bővíthető új menütípusokkal
4. **Többszintű támogatás**: Korlátlan mélységű menühierarchia
5. **Típusbiztos**: TypeScript típusok a teljes stack-en
6. **Újrafelhasználható**: Ugyanaz a komponens használható különböző menütípusokhoz

## Tesztelés

A `fake_internal_api/endpoints/menu.php` tartalmaz példa menüstruktúrákat teszteléshez.

**Példa kérés:**
```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"type":"protected"}' \
  "http://localhost/api/menu"
```

## Jövőbeli Fejlesztések

- Menü cache-elés a kliens oldalon
- Menü frissítés real-time (WebSocket)
- Menü keresés funkció
- Menü személyre szabás (felhasználó által rendezett menü)
- Menü analytics (melyik menüpontot használják a legtöbbet)
