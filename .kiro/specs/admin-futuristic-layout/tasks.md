# Implementation Plan: Admin Futuristic Layout

## Overview

Az admin felület futurisztikus átalakításának implementációs terve. A feladat a meglévő admin layout lecserélése egy modern, glassmorphism stílusú designra fix bal oldali sidebarral, Tailwind CSS 4 használatával.

## Tasks

- [x] 1. Tailwind CSS 4 theme konfiguráció
  - [x] 1.1 Admin színpaletta hozzáadása az app.css fájlhoz
    - `@theme` direktívával definiálni az admin színeket
    - Háttér, akcentus, szöveg és border színek
    - _Requirements: 2.1, 2.3_

- [x] 2. Admin komponensek létrehozása
  - [x] 2.1 AdminNavItem.svelte komponens
    - Egyedi menüpont komponens ikon támogatással
    - Aktív és hover állapot stílusok
    - _Requirements: 3.2, 3.3, 3.4_
  - [x] 2.2 AdminUserInfo.svelte komponens
    - Felhasználó név és avatar megjelenítése
    - Kijelentkezés gomb funkcionalitással
    - _Requirements: 3.6_
  - [x] 2.3 AdminSidebar.svelte komponens
    - Logo/brand section
    - Navigációs menüpontok (Dashboard, Felhasználók, Statisztikák, Hibák)
    - User info section integrálása
    - Glassmorphism stílus
    - _Requirements: 3.1, 3.2, 3.4, 3.5, 3.6, 2.2_
  - [x] 2.4 Property test: Menu Items Completeness
    - **Property 1: Menu Items Completeness**
    - **Validates: Requirements 3.1**
  - [x] 2.5 Property test: Active State Correctness
    - **Property 2: Active State Correctness**
    - **Validates: Requirements 3.2**

- [x] 3. AdminLayout komponens és integráció
  - [x] 3.1 AdminLayout.svelte komponens
    - Fix sidebar + scrollable content area grid layout
    - Mobil menü állapot kezelése (hamburger button)
    - Overlay backdrop mobil nézetben
    - _Requirements: 1.1, 1.2, 1.3, 5.1, 5.2, 5.3_
  - [x] 3.2 Mobil menü click-outside bezárás
    - Kattintás a sidebar-on kívül bezárja a menüt
    - _Requirements: 5.4_
  - [x] 3.3 Property test: Mobile Menu Toggle Behavior
    - **Property 3: Mobile Menu Toggle Behavior**
    - **Validates: Requirements 5.4**

- [x] 4. Admin route layout frissítése
  - [x] 4.1 (admin)/+layout.svelte frissítése
    - AdminLayout komponens integrálása
    - Meglévő auth guard logika megtartása
    - _Requirements: 1.1_

- [x] 5. Admin Dashboard főoldal átalakítása
  - [x] 5.1 admin/+page.svelte futurisztikus redesign
    - Welcome section a felhasználó nevével
    - Glossy navigációs kártyák (Felhasználók, Statisztikák, Hibák)
    - Jogosultságok megjelenítése
    - Hover animációk és glow effektek
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 2.4, 2.5_
  - [x] 5.2 Property test: User Name Display
    - **Property 4: User Name Display**
    - **Validates: Requirements 6.1**
  - [x] 5.3 Property test: Permissions Display Completeness
    - **Property 5: Permissions Display Completeness**
    - **Validates: Requirements 6.4**

- [x] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Animációk és finomhangolás
  - [x] 7.1 Staggered fade-in animáció a sidebar menüpontokhoz
    - CSS keyframes és animation-delay
    - _Requirements: 4.1_
  - [x] 7.2 Page transition animációk
    - Content area fade transition navigációkor
    - _Requirements: 4.2_
  - [x] 7.3 Hover effektek finomhangolása
    - Scale és glow effektek kártyákon és gombokon
    - Smooth transitions (200-300ms)
    - _Requirements: 4.3, 4.4_

- [x] 8. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- A projekt Tailwind CSS 4-et használ `@theme` direktívával
- A meglévő auth guard logika megmarad a `(admin)/+layout.svelte`-ben
- A komponensek a `frontend/src/lib/components/admin/` mappába kerülnek
- Property tesztek Vitest és fast-check könyvtárakat használnak
