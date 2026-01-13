# Requirements Document

## Introduction

Az admin felület vizuális átalakítása futurisztikus, letisztult, "glossy" megjelenésre. A cél egy modern, professzionális admin dashboard létrehozása fix bal oldali sidebarral, amely tartalmazza az összes admin menüpontot. A design sötét témájú, üveghatású (glassmorphism) elemekkel, finom animációkkal és neon akcentusokkal.

## Glossary

- **Admin_Layout**: Az admin felület fő elrendezési komponense, amely tartalmazza a sidebárt és a fő tartalmi területet
- **Admin_Sidebar**: A bal oldali fix navigációs sáv, amely az admin menüpontokat tartalmazza
- **Glassmorphism**: Üveghatású design stílus, amely átlátszó/félig átlátszó hátteret, elmosódást és finom szegélyeket használ
- **Glossy_Effect**: Fényes, tükröződő felületi hatás CSS gradientekkel és árnyékokkal megvalósítva
- **Admin_Content_Area**: A fő tartalmi terület, ahol az admin oldalak tartalma megjelenik
- **Menu_Item**: Egy navigációs elem a sidebarban, amely egy admin aloldalra mutat
- **Active_State**: A jelenleg kiválasztott menüpont vizuális állapota

## Requirements

### Requirement 1: Admin Layout Struktúra

**User Story:** As an admin user, I want a consistent layout with a fixed sidebar, so that I can easily navigate between admin sections.

#### Acceptance Criteria

1. WHEN an admin user visits any admin page, THE Admin_Layout SHALL display a fixed sidebar on the left side and the main content area on the right
2. THE Admin_Sidebar SHALL have a fixed width of 280px and remain visible during scrolling
3. THE Admin_Content_Area SHALL fill the remaining horizontal space and be scrollable independently
4. WHEN the viewport width is less than 768px, THE Admin_Layout SHALL collapse the sidebar into a hamburger menu

### Requirement 2: Futurisztikus Vizuális Megjelenés

**User Story:** As an admin user, I want a modern, futuristic interface, so that the admin experience feels premium and professional.

#### Acceptance Criteria

1. THE Admin_Layout SHALL use a dark theme with a primary background color of #0a0a0f (deep space black)
2. THE Admin_Sidebar SHALL apply Glassmorphism effect with semi-transparent background (rgba(15, 15, 25, 0.8)), backdrop blur, and subtle border
3. THE Admin_Layout SHALL use a consistent color palette with cyan (#00d4ff) and purple (#8b5cf6) as accent colors
4. WHEN hovering over interactive elements, THE Admin_Layout SHALL display smooth transition animations (200-300ms duration)
5. THE Admin_Layout SHALL apply Glossy_Effect to cards and panels using gradient overlays

### Requirement 3: Admin Sidebar Navigáció

**User Story:** As an admin user, I want clear navigation in the sidebar, so that I can quickly access different admin functions.

#### Acceptance Criteria

1. THE Admin_Sidebar SHALL display the following menu items: Dashboard, Felhasználók, Statisztikák, Hibák
2. WHEN a Menu_Item is in Active_State, THE Admin_Sidebar SHALL highlight it with the accent color and a left border indicator
3. WHEN hovering over a Menu_Item, THE Admin_Sidebar SHALL display a subtle glow effect
4. THE Admin_Sidebar SHALL display an icon next to each Menu_Item label
5. THE Admin_Sidebar SHALL include a logo/brand section at the top
6. THE Admin_Sidebar SHALL include a user info section at the bottom with logout functionality

### Requirement 4: Animációk és Átmenetek

**User Story:** As an admin user, I want smooth animations, so that the interface feels responsive and polished.

#### Acceptance Criteria

1. WHEN the page loads, THE Admin_Sidebar menu items SHALL animate in with a staggered fade-in effect
2. WHEN navigating between pages, THE Admin_Content_Area SHALL apply a subtle fade transition
3. WHEN hovering over cards or buttons, THE Admin_Layout SHALL apply a subtle scale and glow effect
4. THE Admin_Layout SHALL use CSS transitions for all interactive state changes

### Requirement 5: Reszponzív Viselkedés

**User Story:** As an admin user, I want the admin interface to work on different screen sizes, so that I can manage the system from various devices.

#### Acceptance Criteria

1. WHEN the viewport width is 768px or greater, THE Admin_Sidebar SHALL be permanently visible
2. WHEN the viewport width is less than 768px, THE Admin_Sidebar SHALL be hidden by default and accessible via a hamburger menu button
3. WHEN the mobile menu is opened, THE Admin_Sidebar SHALL slide in from the left with an overlay backdrop
4. WHEN clicking outside the sidebar on mobile, THE Admin_Layout SHALL close the mobile menu

### Requirement 6: Admin Dashboard Főoldal

**User Story:** As an admin user, I want an informative dashboard, so that I can see an overview of the system at a glance.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL display a welcome section with the user's name
2. THE Admin_Dashboard SHALL display navigation cards for each admin section (Felhasználók, Statisztikák, Hibák)
3. THE navigation cards SHALL apply Glossy_Effect and hover animations
4. THE Admin_Dashboard SHALL display the user's current permissions
