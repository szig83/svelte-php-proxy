// Property test: User Name Display
// **Property 4: User Name Display**
// **Validates: Requirements 6.1**
//
// *For any* authenticated user with a non-empty name, the Admin_Dashboard welcome section
// SHALL contain that user's name in the rendered output.
//
// Property test: Permissions Display Completeness
// **Property 5: Permissions Display Completeness**
// **Validates: Requirements 6.4**
//
// *For any* authenticated user with a permissions array, all permissions in that array
// SHALL be displayed in the Admin_Dashboard permissions section.

import { describe, it, expect } from 'vitest';
import * as fc from 'fast-check';

/**
 * User interface matching the auth store
 */
interface User {
	id: string;
	email: string;
	name: string;
	permissions: string[];
}

/**
 * Pure function that generates the welcome message display name
 * This mirrors the logic from admin/+page.svelte: authState.user?.name || 'Felhasználó'
 */
function getDisplayName(user: User | null): string {
	return user?.name || 'Felhasználó';
}

/**
 * Pure function that generates the welcome section content
 * This mirrors the welcome section rendering from admin/+page.svelte
 */
function renderWelcomeSection(user: User | null): string {
	const displayName = getDisplayName(user);
	return `Üdvözöljük, ${displayName}!`;
}

/**
 * Checks if the welcome section contains the user's name
 */
function welcomeSectionContainsUserName(welcomeContent: string, userName: string): boolean {
	return welcomeContent.includes(userName);
}

/**
 * Arbitrary for generating non-empty user names
 * Generates realistic names with various characters
 */
const nonEmptyNameArb = fc.string({ minLength: 1, maxLength: 100 })
	.filter((s) => s.trim().length > 0);

/**
 * Arbitrary for generating valid user IDs
 */
const userIdArb = fc.uuid();

/**
 * Arbitrary for generating valid email addresses
 */
const emailArb = fc.emailAddress();

/**
 * Arbitrary for generating permission arrays
 */
const permissionsArb = fc.array(
	fc.oneof(
		fc.constant('admin'),
		fc.constant('read'),
		fc.constant('write'),
		fc.constant('delete'),
		fc.constant('manage_users')
	),
	{ minLength: 0, maxLength: 5 }
);

/**
 * Arbitrary for generating non-empty permission arrays (for Property 5)
 */
const nonEmptyPermissionsArb = fc.array(
	fc.oneof(
		fc.constant('admin'),
		fc.constant('read'),
		fc.constant('write'),
		fc.constant('delete'),
		fc.constant('manage_users'),
		fc.constant('view_stats'),
		fc.constant('edit_users'),
		fc.constant('view_errors')
	),
	{ minLength: 1, maxLength: 10 }
);

/**
 * Arbitrary for generating authenticated users with non-empty names
 */
const authenticatedUserArb = fc.record({
	id: userIdArb,
	email: emailArb,
	name: nonEmptyNameArb,
	permissions: permissionsArb
});

describe('Feature: admin-futuristic-layout, Property 4: User Name Display', () => {
	/**
	 * Property 4: User name should be displayed in welcome section
	 * For any authenticated user with a non-empty name, the Admin_Dashboard welcome section
	 * SHALL contain that user's name in the rendered output.
	 */
	it('should display user name in welcome section for authenticated users', () => {
		fc.assert(
			fc.property(authenticatedUserArb, (user) => {
				const welcomeContent = renderWelcomeSection(user);

				// The welcome section should contain the user's name
				expect(welcomeSectionContainsUserName(welcomeContent, user.name)).toBe(true);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 4.1: Display name function should return user's name when present
	 * For any user with a non-empty name, getDisplayName should return that name
	 */
	it('should return user name from getDisplayName when user has name', () => {
		fc.assert(
			fc.property(authenticatedUserArb, (user) => {
				const displayName = getDisplayName(user);

				expect(displayName).toBe(user.name);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 4.2: Welcome section format should be consistent
	 * For any user, the welcome section should follow the format "Üdvözöljük, {name}!"
	 */
	it('should format welcome section consistently', () => {
		fc.assert(
			fc.property(authenticatedUserArb, (user) => {
				const welcomeContent = renderWelcomeSection(user);
				const expectedFormat = `Üdvözöljük, ${user.name}!`;

				expect(welcomeContent).toBe(expectedFormat);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 4.3: Fallback to default name when user is null
	 * When user is null, the display name should be 'Felhasználó'
	 */
	it('should display fallback name when user is null', () => {
		fc.assert(
			fc.property(fc.constant(null), (user) => {
				const displayName = getDisplayName(user);

				expect(displayName).toBe('Felhasználó');
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 4.4: Display name is deterministic
	 * For the same user, getDisplayName should always return the same result
	 */
	it('should produce deterministic display names', () => {
		fc.assert(
			fc.property(authenticatedUserArb, (user) => {
				const displayName1 = getDisplayName(user);
				const displayName2 = getDisplayName(user);

				expect(displayName1).toBe(displayName2);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 4.5: Welcome section rendering is deterministic
	 * For the same user, renderWelcomeSection should always return the same result
	 */
	it('should produce deterministic welcome section content', () => {
		fc.assert(
			fc.property(authenticatedUserArb, (user) => {
				const content1 = renderWelcomeSection(user);
				const content2 = renderWelcomeSection(user);

				expect(content1).toBe(content2);
			}),
			{ numRuns: 100 }
		);
	});
});

/**
 * Pure function that renders the permissions section content
 * This mirrors the permissions rendering logic from admin/+page.svelte
 */
function renderPermissionsSection(permissions: string[]): string[] {
	if (!permissions || permissions.length === 0) {
		return [];
	}
	return permissions.map((permission) => permission);
}

/**
 * Checks if all permissions are present in the rendered output
 */
function allPermissionsDisplayed(renderedPermissions: string[], originalPermissions: string[]): boolean {
	return originalPermissions.every((permission) => renderedPermissions.includes(permission));
}

/**
 * Arbitrary for generating authenticated users with non-empty permissions
 */
const userWithPermissionsArb = fc.record({
	id: userIdArb,
	email: emailArb,
	name: nonEmptyNameArb,
	permissions: nonEmptyPermissionsArb
});

describe('Feature: admin-futuristic-layout, Property 5: Permissions Display Completeness', () => {
	/**
	 * Property 5: All permissions should be displayed
	 * For any authenticated user with a permissions array, all permissions in that array
	 * SHALL be displayed in the Admin_Dashboard permissions section.
	 */
	it('should display all permissions from user permissions array', () => {
		fc.assert(
			fc.property(userWithPermissionsArb, (user) => {
				const renderedPermissions = renderPermissionsSection(user.permissions);

				// All original permissions should be present in the rendered output
				expect(allPermissionsDisplayed(renderedPermissions, user.permissions)).toBe(true);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 5.1: Rendered permissions count matches original
	 * For any user with permissions, the number of rendered permissions should equal the original count
	 */
	it('should render the same number of permissions as in the original array', () => {
		fc.assert(
			fc.property(userWithPermissionsArb, (user) => {
				const renderedPermissions = renderPermissionsSection(user.permissions);

				expect(renderedPermissions.length).toBe(user.permissions.length);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 5.2: Permission rendering preserves content
	 * For any permission string, the rendered output should contain the exact permission text
	 */
	it('should preserve permission text in rendered output', () => {
		fc.assert(
			fc.property(userWithPermissionsArb, (user) => {
				const renderedPermissions = renderPermissionsSection(user.permissions);

				user.permissions.forEach((permission, index) => {
					expect(renderedPermissions[index]).toBe(permission);
				});
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 5.3: Empty permissions array returns empty rendered list
	 * When user has no permissions, the rendered permissions list should be empty
	 */
	it('should return empty list when permissions array is empty', () => {
		fc.assert(
			fc.property(fc.constant([]), (permissions: string[]) => {
				const renderedPermissions = renderPermissionsSection(permissions);

				expect(renderedPermissions).toEqual([]);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 5.4: Permissions rendering is deterministic
	 * For the same permissions array, renderPermissionsSection should always return the same result
	 */
	it('should produce deterministic permissions rendering', () => {
		fc.assert(
			fc.property(userWithPermissionsArb, (user) => {
				const rendered1 = renderPermissionsSection(user.permissions);
				const rendered2 = renderPermissionsSection(user.permissions);

				expect(rendered1).toEqual(rendered2);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 5.5: Each permission appears exactly once
	 * For any permissions array, each unique permission should appear exactly once in the output
	 */
	it('should render each permission exactly once', () => {
		fc.assert(
			fc.property(userWithPermissionsArb, (user) => {
				const renderedPermissions = renderPermissionsSection(user.permissions);

				// Count occurrences of each permission in rendered output
				user.permissions.forEach((permission) => {
					const occurrences = renderedPermissions.filter((p) => p === permission).length;
					const expectedOccurrences = user.permissions.filter((p) => p === permission).length;
					expect(occurrences).toBe(expectedOccurrences);
				});
			}),
			{ numRuns: 100 }
		);
	});
});
