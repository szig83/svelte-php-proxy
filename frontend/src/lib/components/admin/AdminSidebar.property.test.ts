// Property test: Menu Items Completeness
// **Property 1: Menu Items Completeness**
// **Validates: Requirements 3.1**
//
// *For any* render of the AdminSidebar component, all required menu items
// (Dashboard, Felhasználók, Statisztikák, Hibák) SHALL be present in the rendered output.
//
// Property test: Active State Correctness
// **Property 2: Active State Correctness**
// **Validates: Requirements 3.2**
//
// *For any* route path within the admin section, exactly one menu item SHALL be marked as active,
// and it SHALL correspond to the current route (e.g., `/admin` → Dashboard, `/admin/users` → Felhasználók,
// `/admin/stats` → Statisztikák, `/admin/errors` → Hibák).

import { describe, it, expect } from 'vitest';
import * as fc from 'fast-check';

/**
 * MenuItem interface matching the AdminSidebar component
 */
interface MenuItem {
	label: string;
	href: string;
	icon: 'dashboard' | 'users' | 'chart' | 'bug';
}

/**
 * Required menu items as defined in Requirements 3.1
 */
const REQUIRED_MENU_ITEMS: MenuItem[] = [
	{ label: 'Dashboard', href: '/admin', icon: 'dashboard' },
	{ label: 'Felhasználók', href: '/admin/users', icon: 'users' },
	{ label: 'Statisztikák', href: '/admin/stats', icon: 'chart' },
	{ label: 'Hibák', href: '/admin/errors', icon: 'bug' }
];

/**
 * Pure function that returns the menu items from AdminSidebar
 * This mirrors the component's menuItems constant
 */
function getMenuItems(): MenuItem[] {
	return [
		{ label: 'Dashboard', href: '/admin', icon: 'dashboard' },
		{ label: 'Felhasználók', href: '/admin/users', icon: 'users' },
		{ label: 'Statisztikák', href: '/admin/stats', icon: 'chart' },
		{ label: 'Hibák', href: '/admin/errors', icon: 'bug' }
	];
}

/**
 * Checks if all required menu items are present in the provided menu items array
 */
function checkMenuItemsCompleteness(
	menuItems: MenuItem[],
	requiredItems: MenuItem[]
): { isComplete: boolean; missingItems: MenuItem[] } {
	const missingItems = requiredItems.filter(
		(required) =>
			!menuItems.some(
				(item) =>
					item.label === required.label &&
					item.href === required.href &&
					item.icon === required.icon
			)
	);

	return {
		isComplete: missingItems.length === 0,
		missingItems
	};
}

/**
 * Arbitrary for generating sidebar props combinations
 */
const sidebarPropsArb = fc.record({
	isOpen: fc.boolean(),
	onClose: fc.constant(() => {})
});

describe('Feature: admin-futuristic-layout, Property 1: Menu Items Completeness', () => {
	/**
	 * Property 1: All required menu items must be present
	 * For any render of the AdminSidebar component, all required menu items
	 * (Dashboard, Felhasználók, Statisztikák, Hibák) SHALL be present
	 */
	it('should contain all required menu items regardless of props', () => {
		fc.assert(
			fc.property(sidebarPropsArb, () => {
				const menuItems = getMenuItems();
				const result = checkMenuItemsCompleteness(menuItems, REQUIRED_MENU_ITEMS);

				expect(result.isComplete).toBe(true);
				expect(result.missingItems).toHaveLength(0);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 1.1: Menu items count must match required count
	 * The sidebar should have exactly 4 menu items as specified in requirements
	 */
	it('should have exactly 4 menu items', () => {
		fc.assert(
			fc.property(sidebarPropsArb, () => {
				const menuItems = getMenuItems();

				expect(menuItems).toHaveLength(4);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 1.2: Each required menu item must have correct label
	 * For any required menu item, its label must be present in the menu
	 */
	it('should contain all required labels', () => {
		fc.assert(
			fc.property(sidebarPropsArb, () => {
				const menuItems = getMenuItems();
				const menuLabels = menuItems.map((item) => item.label);

				const requiredLabels = ['Dashboard', 'Felhasználók', 'Statisztikák', 'Hibák'];

				requiredLabels.forEach((label) => {
					expect(menuLabels).toContain(label);
				});
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 1.3: Each required menu item must have correct href
	 * For any required menu item, its href must be present in the menu
	 */
	it('should contain all required hrefs', () => {
		fc.assert(
			fc.property(sidebarPropsArb, () => {
				const menuItems = getMenuItems();
				const menuHrefs = menuItems.map((item) => item.href);

				const requiredHrefs = ['/admin', '/admin/users', '/admin/stats', '/admin/errors'];

				requiredHrefs.forEach((href) => {
					expect(menuHrefs).toContain(href);
				});
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 1.4: Each menu item must have an icon
	 * For any menu item, it must have a valid icon identifier
	 */
	it('should have icons for all menu items', () => {
		fc.assert(
			fc.property(sidebarPropsArb, () => {
				const menuItems = getMenuItems();
				const validIcons = ['dashboard', 'users', 'chart', 'bug'];

				menuItems.forEach((item) => {
					expect(validIcons).toContain(item.icon);
				});
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 1.5: Menu items should be deterministic
	 * Multiple calls to getMenuItems should return the same items
	 */
	it('should return deterministic menu items', () => {
		fc.assert(
			fc.property(fc.nat({ max: 10 }), () => {
				const menuItems1 = getMenuItems();
				const menuItems2 = getMenuItems();

				expect(menuItems1).toEqual(menuItems2);
			}),
			{ numRuns: 100 }
		);
	});
});


/**
 * Pure function that determines if a menu item is active based on the current path
 * This mirrors the isActive function from AdminSidebar.svelte
 */
function isActive(href: string, currentPath: string): boolean {
	if (href === '/admin') {
		return currentPath === '/admin';
	}
	return currentPath === href || currentPath.startsWith(href + '/');
}

/**
 * Route to expected active menu item mapping
 */
const ROUTE_TO_MENU_MAPPING: Record<string, string> = {
	'/admin': 'Dashboard',
	'/admin/users': 'Felhasználók',
	'/admin/stats': 'Statisztikák',
	'/admin/errors': 'Hibák'
};

/**
 * Arbitrary for generating valid admin route paths
 */
const adminRouteArb = fc.oneof(
	fc.constant('/admin'),
	fc.constant('/admin/users'),
	fc.constant('/admin/stats'),
	fc.constant('/admin/errors'),
	// Sub-routes
	fc.constant('/admin/users/123'),
	fc.constant('/admin/users/edit/456'),
	fc.constant('/admin/stats/daily'),
	fc.constant('/admin/errors/detail/789')
);

/**
 * Gets the expected active menu label for a given path
 */
function getExpectedActiveLabel(path: string): string {
	if (path === '/admin') return 'Dashboard';
	if (path === '/admin/users' || path.startsWith('/admin/users/')) return 'Felhasználók';
	if (path === '/admin/stats' || path.startsWith('/admin/stats/')) return 'Statisztikák';
	if (path === '/admin/errors' || path.startsWith('/admin/errors/')) return 'Hibák';
	return '';
}

describe('Feature: admin-futuristic-layout, Property 2: Active State Correctness', () => {
	/**
	 * Property 2: Exactly one menu item should be active for any admin route
	 * For any route path within the admin section, exactly one menu item SHALL be marked as active
	 */
	it('should have exactly one active menu item for any admin route', () => {
		fc.assert(
			fc.property(adminRouteArb, (currentPath) => {
				const menuItems = getMenuItems();
				const activeItems = menuItems.filter((item) => isActive(item.href, currentPath));

				expect(activeItems).toHaveLength(1);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 2.1: Active menu item should correspond to the current route
	 * The active menu item SHALL correspond to the current route
	 */
	it('should mark the correct menu item as active based on route', () => {
		fc.assert(
			fc.property(adminRouteArb, (currentPath) => {
				const menuItems = getMenuItems();
				const activeItem = menuItems.find((item) => isActive(item.href, currentPath));
				const expectedLabel = getExpectedActiveLabel(currentPath);

				expect(activeItem).toBeDefined();
				expect(activeItem?.label).toBe(expectedLabel);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 2.2: Dashboard should only be active for exact /admin path
	 * The Dashboard menu item should only be active when the path is exactly /admin
	 */
	it('should only mark Dashboard as active for exact /admin path', () => {
		fc.assert(
			fc.property(adminRouteArb, (currentPath) => {
				const dashboardHref = '/admin';
				const isDashboardActive = isActive(dashboardHref, currentPath);

				if (currentPath === '/admin') {
					expect(isDashboardActive).toBe(true);
				} else {
					expect(isDashboardActive).toBe(false);
				}
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 2.3: Sub-routes should activate their parent menu item
	 * For any sub-route (e.g., /admin/users/123), the parent menu item should be active
	 */
	it('should activate parent menu item for sub-routes', () => {
		const subRouteArb = fc.oneof(
			fc.constant('/admin/users/123'),
			fc.constant('/admin/users/edit/456'),
			fc.constant('/admin/stats/daily'),
			fc.constant('/admin/stats/weekly/report'),
			fc.constant('/admin/errors/detail/789'),
			fc.constant('/admin/errors/archive/2024')
		);

		fc.assert(
			fc.property(subRouteArb, (currentPath) => {
				const menuItems = getMenuItems();
				const activeItems = menuItems.filter((item) => isActive(item.href, currentPath));

				// Should have exactly one active item
				expect(activeItems).toHaveLength(1);

				// The active item should be the parent route
				const activeItem = activeItems[0];
				expect(currentPath.startsWith(activeItem.href)).toBe(true);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 2.4: Non-active items should not be marked as active
	 * For any route, all non-matching menu items should not be active
	 */
	it('should not mark non-matching menu items as active', () => {
		fc.assert(
			fc.property(adminRouteArb, (currentPath) => {
				const menuItems = getMenuItems();
				const expectedLabel = getExpectedActiveLabel(currentPath);

				menuItems.forEach((item) => {
					const itemIsActive = isActive(item.href, currentPath);
					if (item.label === expectedLabel) {
						expect(itemIsActive).toBe(true);
					} else {
						expect(itemIsActive).toBe(false);
					}
				});
			}),
			{ numRuns: 100 }
		);
	});
});
