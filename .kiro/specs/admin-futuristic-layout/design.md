# Design Document: Admin Futuristic Layout

## Overview

Ez a dokumentum az admin felület futurisztikus átalakításának technikai tervét tartalmazza. A megoldás SvelteKit és **Tailwind CSS 4** alapú, a meglévő admin route struktúrára épül. A design glassmorphism stílust alkalmaz sötét témával, fix bal oldali sidebarral és reszponzív viselkedéssel.

### Tailwind CSS 4 Specifikus Megoldások

A projekt Tailwind CSS 4-et használ, amely új szintaxist és funkciókat kínál:

- **CSS-first konfiguráció**: A `@theme` direktíva használata a `tailwind.config.js` helyett
- **Natív CSS változók**: A design tokenek CSS custom properties-ként definiáltak
- **Új utility osztályok**: `backdrop-blur-*`, `bg-*/opacity` szintaxis
- **Container queries**: `@container` támogatás reszponzív komponensekhez

## Architecture

### Komponens Hierarchia

```
(admin)/+layout.svelte
├── AdminLayout.svelte (új wrapper komponens)
│   ├── AdminSidebar.svelte (új sidebar komponens)
│   │   ├── Logo/Brand section
│   │   ├── Navigation menu items
│   │   └── User info + logout
│   └── Main content area
│       └── {@render children()}
```

### Fájl Struktúra

```
frontend/src/lib/components/admin/
├── AdminLayout.svelte      # Fő layout wrapper
├── AdminSidebar.svelte     # Sidebar komponens
├── AdminNavItem.svelte     # Navigációs elem komponens
└── AdminUserInfo.svelte    # User info section
```

### Route Integráció

A `(admin)/+layout.svelte` fájl fogja használni az `AdminLayout` komponenst, amely tartalmazza a sidebárt és a content area-t.

## Components and Interfaces

### AdminLayout.svelte

```svelte
<script lang="ts">
  import AdminSidebar from './AdminSidebar.svelte';

  interface Props {
    children: Snippet;
  }

  let { children }: Props = $props();
  let isMobileMenuOpen = $state(false);
</script>
```

Felelősségek:
- Fő layout grid kezelése (sidebar + content)
- Mobil menü állapot kezelése
- Reszponzív breakpoint kezelés

### AdminSidebar.svelte

```svelte
<script lang="ts">
  import { page } from '$app/stores';
  import AdminNavItem from './AdminNavItem.svelte';
  import AdminUserInfo from './AdminUserInfo.svelte';

  interface Props {
    isOpen?: boolean;
    onClose?: () => void;
  }

  let { isOpen = true, onClose }: Props = $props();

  const menuItems = [
    { label: 'Dashboard', href: '/admin', icon: 'dashboard' },
    { label: 'Felhasználók', href: '/admin/users', icon: 'users' },
    { label: 'Statisztikák', href: '/admin/stats', icon: 'chart' },
    { label: 'Hibák', href: '/admin/errors', icon: 'bug' }
  ];
</script>
```

Felelősségek:
- Menüpontok megjelenítése
- Aktív állapot kezelése az aktuális route alapján
- Logo és user info megjelenítése
- Mobil overlay és animációk

### AdminNavItem.svelte

```svelte
<script lang="ts">
  interface Props {
    label: string;
    href: string;
    icon: string;
    isActive: boolean;
  }

  let { label, href, icon, isActive }: Props = $props();
</script>
```

Felelősségek:
- Egyedi menüpont megjelenítése
- Ikon renderelés
- Hover és aktív állapot stílusok

### AdminUserInfo.svelte

```svelte
<script lang="ts">
  import { getAuthState, logout } from '$lib/auth';

  const authState = getAuthState();
</script>
```

Felelősségek:
- Felhasználó név és avatar megjelenítése
- Kijelentkezés gomb

## Data Models

### MenuItem Interface

```typescript
interface MenuItem {
  label: string;      // Megjelenített szöveg
  href: string;       // Navigációs útvonal
  icon: string;       // Ikon azonosító
}
```

### AdminLayoutState

```typescript
interface AdminLayoutState {
  isMobileMenuOpen: boolean;  // Mobil menü nyitva/zárva
  currentPath: string;        // Aktuális route path
}
```

## CSS Design System (Tailwind CSS 4)

### Tailwind CSS 4 Theme Konfiguráció

A design tokeneket az `app.css` fájlban definiáljuk a Tailwind CSS 4 `@theme` direktívával:

```css
@import "tailwindcss";

@theme {
  /* Admin színpaletta */
  --color-admin-bg-primary: #0a0a0f;
  --color-admin-bg-secondary: #12121a;
  --color-admin-bg-card: oklch(10% 0.02 280 / 0.8);

  /* Akcentus színek */
  --color-admin-accent-cyan: #00d4ff;
  --color-admin-accent-purple: #8b5cf6;

  /* Szöveg színek */
  --color-admin-text-primary: #f0f0f5;
  --color-admin-text-secondary: #8888a0;
  --color-admin-text-muted: #555566;

  /* Border */
  --color-admin-border: oklch(100% 0 0 / 0.1);
}
```

### Tailwind CSS 4 Utility Használat

```svelte
<!-- Glassmorphism panel Tailwind 4 szintaxissal -->
<div class="bg-admin-bg-card backdrop-blur-xl border border-admin-border rounded-2xl">

<!-- Gradient háttér -->
<div class="bg-gradient-to-br from-admin-accent-cyan to-admin-accent-purple">

<!-- Hover glow effekt -->
<button class="hover:shadow-[0_0_20px_rgba(0,212,255,0.3)] transition-shadow duration-300">
```

### Színpaletta (CSS Custom Properties Fallback)

```css
:root {
  /* Háttér színek */
  --admin-bg-primary: #0a0a0f;
  --admin-bg-secondary: #12121a;
  --admin-bg-card: rgba(15, 15, 25, 0.8);

  /* Akcentus színek */
  --admin-accent-cyan: #00d4ff;
  --admin-accent-purple: #8b5cf6;
  --admin-accent-gradient: linear-gradient(135deg, #00d4ff, #8b5cf6);

  /* Szöveg színek */
  --admin-text-primary: #f0f0f5;
  --admin-text-secondary: #8888a0;
  --admin-text-muted: #555566;

  /* Border és effektek */
  --admin-border: rgba(255, 255, 255, 0.1);
  --admin-glow-cyan: 0 0 20px rgba(0, 212, 255, 0.3);
  --admin-glow-purple: 0 0 20px rgba(139, 92, 246, 0.3);
}
```

### Glassmorphism Mixin

```css
.glass-panel {
  background: rgba(15, 15, 25, 0.8);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
}
```

### Glossy Effect

```css
.glossy-card {
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.1) 0%,
    rgba(255, 255, 255, 0.05) 50%,
    rgba(255, 255, 255, 0) 100%
  );
  position: relative;
  overflow: hidden;
}

.glossy-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.2),
    transparent
  );
}
```

### Animációk

```css
/* Staggered fade-in */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Glow pulse */
@keyframes glowPulse {
  0%, 100% {
    box-shadow: 0 0 5px rgba(0, 212, 255, 0.2);
  }
  50% {
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
  }
}
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Menu Items Completeness

*For any* render of the AdminSidebar component, all required menu items (Dashboard, Felhasználók, Statisztikák, Hibák) SHALL be present in the rendered output.

**Validates: Requirements 3.1**

### Property 2: Active State Correctness

*For any* route path within the admin section, exactly one menu item SHALL be marked as active, and it SHALL correspond to the current route (e.g., `/admin` → Dashboard, `/admin/users` → Felhasználók, `/admin/stats` → Statisztikák, `/admin/errors` → Hibák).

**Validates: Requirements 3.2**

### Property 3: Mobile Menu Toggle Behavior

*For any* click event outside the sidebar element when the mobile menu is open, the menu state SHALL transition to closed.

**Validates: Requirements 5.4**

### Property 4: User Name Display

*For any* authenticated user with a non-empty name, the Admin_Dashboard welcome section SHALL contain that user's name in the rendered output.

**Validates: Requirements 6.1**

### Property 5: Permissions Display Completeness

*For any* authenticated user with a permissions array, all permissions in that array SHALL be displayed in the Admin_Dashboard permissions section.

**Validates: Requirements 6.4**

## Error Handling

### Authentication Errors

- Ha a felhasználó nincs bejelentkezve, a guard átirányít a login oldalra
- Ha a felhasználó nem admin, a guard átirányít a főoldalra
- A meglévő `(admin)/+layout.svelte` guard logika megmarad

### Rendering Errors

- Ha a user objektum hiányzik vagy üres, alapértelmezett "Felhasználó" szöveg jelenik meg
- Ha a permissions tömb üres, "Nincsenek jogosultságok" üzenet jelenik meg
- Ha egy menüpont ikonja nem található, fallback ikon jelenik meg

### Responsive Errors

- Ha a viewport méret nem detektálható, desktop nézet az alapértelmezett
- Ha a backdrop-filter nem támogatott (régi böngészők), fallback solid háttér

## Testing Strategy

### Unit Tests

A unit tesztek specifikus példákat és edge case-eket tesztelnek:

1. **AdminSidebar rendering**: Ellenőrzi, hogy a sidebar renderel-e minden szükséges elemet
2. **AdminNavItem active state**: Ellenőrzi az aktív állapot helyes megjelenítését
3. **AdminUserInfo logout**: Ellenőrzi a kijelentkezés gomb működését
4. **Mobile menu toggle**: Ellenőrzi a hamburger menü nyitás/zárás működését

### Property-Based Tests

A property tesztek univerzális tulajdonságokat validálnak sok generált inputtal. A tesztek Vitest és fast-check könyvtárakat használnak.

**Konfiguráció:**
- Minimum 100 iteráció property testenként
- Tag formátum: **Feature: admin-futuristic-layout, Property {number}: {property_text}**

**Property Test Implementációk:**

1. **Property 1 - Menu Items Completeness**
   - Generátor: Különböző sidebar props kombinációk
   - Assertion: Minden required menu item megtalálható a renderelt outputban

2. **Property 2 - Active State Correctness**
   - Generátor: Random admin route paths (`/admin`, `/admin/users`, `/admin/stats`, `/admin/errors`)
   - Assertion: Pontosan egy menu item aktív, és az megfelel a route-nak

3. **Property 3 - Mobile Menu Toggle**
   - Generátor: Random click pozíciók a sidebar-on kívül
   - Assertion: Menu state closed-ra vált

4. **Property 4 - User Name Display**
   - Generátor: Random user objektumok nem-üres névvel
   - Assertion: A név megjelenik a welcome sectionben

5. **Property 5 - Permissions Display**
   - Generátor: Random permissions tömbök
   - Assertion: Minden permission megjelenik a renderelt outputban

### Test File Structure

```
frontend/src/lib/components/admin/
├── AdminSidebar.property.test.ts    # Property 1, 2, 3
├── AdminDashboard.property.test.ts  # Property 4, 5
└── AdminLayout.test.ts              # Unit tests
```
