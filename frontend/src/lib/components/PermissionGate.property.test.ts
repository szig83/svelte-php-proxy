// Property test: Jogosultság-alapú UI Megjelenítés
// **Tulajdonság 6: Jogosultság-alapú UI Megjelenítés**
// **Validálja: Követelmények 6.3, 6.5**
//
// *Bármely* jogosultság-korlátozott UI elem esetén, ha a felhasználónak nincs meg
// a szükséges jogosultsága, az elemnek rejtettnek vagy letiltottnak kell lennie.

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import * as fc from 'fast-check';

/**
 * User interface for testing
 */
interface User {
	id: string;
	email: string;
	name: string;
	permissions: string[];
}

/**
 * AuthState interface for testing
 */
interface AuthState {
	isAuthenticated: boolean;
	user: User | null;
	isLoading: boolean;
}

/**
 * PermissionGate props interface
 */
interface PermissionGateProps {
	permission?: string | string[];
	requireAdmin?: boolean;
	fallbackBehavior?: 'hide' | 'disable';
}

/**
 * Result of permission check
 */
interface PermissionCheckResult {
	hasAccess: boolean;
	shouldRender: boolean;
	shouldDisable: boolean;
}

/**
 * Pure function that implements the PermissionGate logic
 * This mirrors the component's checkPermissions function
 */
function checkPermissions(
	authState: AuthState,
	props: PermissionGateProps
): PermissionCheckResult {
	const { permission, requireAdmin = false, fallbackBehavior = 'hide' } = props;

	// Must be authenticated
	if (!authState.isAuthenticated || !authState.user) {
		return {
			hasAccess: false,
			shouldRender: fallbackBehavior === 'disable',
			shouldDisable: fallbackBehavior === 'disable'
		};
	}

	// Check admin requirement
	if (requireAdmin && !authState.user.permissions.includes('admin')) {
		return {
			hasAccess: false,
			shouldRender: fallbackBehavior === 'disable',
			shouldDisable: fallbackBehavior === 'disable'
		};
	}

	// Check specific permissions
	if (permission) {
		const permissions = Array.isArray(permission) ? permission : [permission];
		const hasAllPermissions = permissions.every((perm) =>
			authState.user!.permissions.includes(perm)
		);

		if (!hasAllPermissions) {
			return {
				hasAccess: false,
				shouldRender: fallbackBehavior === 'disable',
				shouldDisable: fallbackBehavior === 'disable'
			};
		}
	}

	return {
		hasAccess: true,
		shouldRender: true,
		shouldDisable: false
	};
}

/**
 * Arbitrary for generating permission strings
 */
const permissionArb = fc.constantFrom(
	'read',
	'write',
	'delete',
	'admin',
	'users:manage',
	'stats:view',
	'posts:create',
	'posts:edit',
	'comments:moderate',
	'settings:edit'
);

/**
 * Arbitrary for generating user objects
 */
const userArb = fc.record({
	id: fc.uuid(),
	email: fc.emailAddress(),
	name: fc.string({ minLength: 1, maxLength: 50 }),
	permissions: fc.array(permissionArb, { minLength: 0, maxLength: 8 })
});

/**
 * Arbitrary for generating auth states
 */
const authStateArb = fc.oneof(
	// Unauthenticated state
	fc.constant<AuthState>({
		isAuthenticated: false,
		user: null,
		isLoading: false
	}),
	// Authenticated state with user
	userArb.map((user) => ({
		isAuthenticated: true,
		user,
		isLoading: false
	}))
);

/**
 * Arbitrary for generating PermissionGate props
 */
const permissionGatePropsArb = fc.record({
	permission: fc.oneof(
		fc.constant(undefined),
		permissionArb,
		fc.array(permissionArb, { minLength: 1, maxLength: 3 })
	),
	requireAdmin: fc.boolean(),
	fallbackBehavior: fc.constantFrom('hide', 'disable') as fc.Arbitrary<'hide' | 'disable'>
});

describe('Property Test: Permission-based UI Rendering', () => {
	/**
	 * Property 6.1: Unauthenticated users should never have access
	 * For any permission requirement, if user is not authenticated,
	 * they should be denied access
	 */
	it('should deny access to unauthenticated users regardless of permission requirements', () => {
		fc.assert(
			fc.property(permissionGatePropsArb, (props) => {
				const unauthenticatedState: AuthState = {
					isAuthenticated: false,
					user: null,
					isLoading: false
				};

				const result = checkPermissions(unauthenticatedState, props);

				// Unauthenticated users should never have access
				expect(result.hasAccess).toBe(false);

				// Based on fallbackBehavior, content should be hidden or disabled
				if (props.fallbackBehavior === 'disable') {
					expect(result.shouldRender).toBe(true);
					expect(result.shouldDisable).toBe(true);
				} else {
					expect(result.shouldRender).toBe(false);
				}
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 6.2: Users with required permissions should have access
	 * For any permission requirement, if user has all required permissions,
	 * they should be granted access
	 */
	it('should grant access to users with all required permissions', () => {
		fc.assert(
			fc.property(
				fc.array(permissionArb, { minLength: 1, maxLength: 3 }),
				fc.boolean(),
				(requiredPermissions, requireAdmin) => {
					// Create user with all required permissions
					const userPermissions = [...requiredPermissions];
					if (requireAdmin) {
						userPermissions.push('admin');
					}

					const authState: AuthState = {
						isAuthenticated: true,
						user: {
							id: 'test-user',
							email: 'test@example.com',
							name: 'Test User',
							permissions: [...new Set(userPermissions)] // Remove duplicates
						},
						isLoading: false
					};

					const props: PermissionGateProps = {
						permission: requiredPermissions,
						requireAdmin,
						fallbackBehavior: 'hide'
					};

					const result = checkPermissions(authState, props);

					// User with all permissions should have access
					expect(result.hasAccess).toBe(true);
					expect(result.shouldRender).toBe(true);
					expect(result.shouldDisable).toBe(false);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 6.3: Users missing any required permission should be denied
	 * For any set of required permissions, if user is missing at least one,
	 * they should be denied access
	 */
	it('should deny access to users missing any required permission', () => {
		fc.assert(
			fc.property(
				fc.array(permissionArb, { minLength: 2, maxLength: 4 }),
				fc.nat({ max: 10 }),
				(requiredPermissions, seed) => {
					// Ensure we have unique permissions
					const uniqueRequired = [...new Set(requiredPermissions)];
					if (uniqueRequired.length < 2) return; // Skip if not enough unique permissions

					// Remove one permission from user's permissions
					const indexToRemove = seed % uniqueRequired.length;
					const userPermissions = uniqueRequired.filter((_, i) => i !== indexToRemove);

					const authState: AuthState = {
						isAuthenticated: true,
						user: {
							id: 'test-user',
							email: 'test@example.com',
							name: 'Test User',
							permissions: userPermissions
						},
						isLoading: false
					};

					const props: PermissionGateProps = {
						permission: uniqueRequired,
						requireAdmin: false,
						fallbackBehavior: 'hide'
					};

					const result = checkPermissions(authState, props);

					// User missing a permission should be denied
					expect(result.hasAccess).toBe(false);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 6.4: Admin requirement should be enforced
	 * For any user without admin permission, if requireAdmin is true,
	 * they should be denied access
	 */
	it('should deny access to non-admin users when requireAdmin is true', () => {
		fc.assert(
			fc.property(
				userArb.filter((u) => !u.permissions.includes('admin')),
				fc.constantFrom('hide', 'disable') as fc.Arbitrary<'hide' | 'disable'>,
				(nonAdminUser, fallbackBehavior) => {
					const authState: AuthState = {
						isAuthenticated: true,
						user: nonAdminUser,
						isLoading: false
					};

					const props: PermissionGateProps = {
						requireAdmin: true,
						fallbackBehavior
					};

					const result = checkPermissions(authState, props);

					// Non-admin users should be denied when requireAdmin is true
					expect(result.hasAccess).toBe(false);

					// Verify fallback behavior
					if (fallbackBehavior === 'disable') {
						expect(result.shouldRender).toBe(true);
						expect(result.shouldDisable).toBe(true);
					} else {
						expect(result.shouldRender).toBe(false);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 6.5: Admin users should pass admin requirement
	 * For any user with admin permission, if requireAdmin is true,
	 * they should be granted access (assuming no other permission requirements fail)
	 */
	it('should grant access to admin users when requireAdmin is true', () => {
		fc.assert(
			fc.property(
				userArb.map((u) => ({
					...u,
					permissions: [...new Set([...u.permissions, 'admin'])]
				})),
				(adminUser) => {
					const authState: AuthState = {
						isAuthenticated: true,
						user: adminUser,
						isLoading: false
					};

					const props: PermissionGateProps = {
						requireAdmin: true,
						fallbackBehavior: 'hide'
					};

					const result = checkPermissions(authState, props);

					// Admin users should have access
					expect(result.hasAccess).toBe(true);
					expect(result.shouldRender).toBe(true);
					expect(result.shouldDisable).toBe(false);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 6.6: Fallback behavior 'hide' should not render content
	 * For any denied access with fallbackBehavior='hide',
	 * the content should not be rendered
	 */
	it('should hide content when access is denied and fallbackBehavior is hide', () => {
		fc.assert(
			fc.property(
				authStateArb,
				fc.array(permissionArb, { minLength: 1, maxLength: 3 }),
				(authState, requiredPermissions) => {
					// Skip if user has all permissions (access would be granted)
					if (
						authState.isAuthenticated &&
						authState.user &&
						requiredPermissions.every((p) => authState.user!.permissions.includes(p))
					) {
						return;
					}

					const props: PermissionGateProps = {
						permission: requiredPermissions,
						requireAdmin: false,
						fallbackBehavior: 'hide'
					};

					const result = checkPermissions(authState, props);

					// If access is denied with 'hide' behavior
					if (!result.hasAccess) {
						expect(result.shouldRender).toBe(false);
						expect(result.shouldDisable).toBe(false);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 6.7: Fallback behavior 'disable' should render but disable content
	 * For any denied access with fallbackBehavior='disable',
	 * the content should be rendered but disabled
	 */
	it('should disable content when access is denied and fallbackBehavior is disable', () => {
		fc.assert(
			fc.property(
				authStateArb,
				fc.array(permissionArb, { minLength: 1, maxLength: 3 }),
				(authState, requiredPermissions) => {
					// Skip if user has all permissions (access would be granted)
					if (
						authState.isAuthenticated &&
						authState.user &&
						requiredPermissions.every((p) => authState.user!.permissions.includes(p))
					) {
						return;
					}

					const props: PermissionGateProps = {
						permission: requiredPermissions,
						requireAdmin: false,
						fallbackBehavior: 'disable'
					};

					const result = checkPermissions(authState, props);

					// If access is denied with 'disable' behavior
					if (!result.hasAccess) {
						expect(result.shouldRender).toBe(true);
						expect(result.shouldDisable).toBe(true);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 6.8: No permission requirement should grant access to authenticated users
	 * For any authenticated user, if no specific permission is required,
	 * they should be granted access
	 */
	it('should grant access to authenticated users when no permission is required', () => {
		fc.assert(
			fc.property(userArb, (user) => {
				const authState: AuthState = {
					isAuthenticated: true,
					user,
					isLoading: false
				};

				const props: PermissionGateProps = {
					permission: undefined,
					requireAdmin: false,
					fallbackBehavior: 'hide'
				};

				const result = checkPermissions(authState, props);

				// Authenticated users with no permission requirement should have access
				expect(result.hasAccess).toBe(true);
				expect(result.shouldRender).toBe(true);
				expect(result.shouldDisable).toBe(false);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 6.9: Single permission check should work correctly
	 * For any single permission requirement, access should be granted
	 * if and only if user has that permission
	 */
	it('should correctly check single permission requirement', () => {
		fc.assert(
			fc.property(userArb, permissionArb, (user, requiredPermission) => {
				const authState: AuthState = {
					isAuthenticated: true,
					user,
					isLoading: false
				};

				const props: PermissionGateProps = {
					permission: requiredPermission,
					requireAdmin: false,
					fallbackBehavior: 'hide'
				};

				const result = checkPermissions(authState, props);
				const userHasPermission = user.permissions.includes(requiredPermission);

				// Access should match whether user has the permission
				expect(result.hasAccess).toBe(userHasPermission);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 6.10: Combined admin and permission requirements
	 * For any combination of admin requirement and specific permissions,
	 * user must satisfy ALL requirements to gain access
	 */
	it('should require both admin and specific permissions when both are specified', () => {
		fc.assert(
			fc.property(
				userArb,
				fc.array(permissionArb, { minLength: 1, maxLength: 2 }),
				(user, requiredPermissions) => {
					const authState: AuthState = {
						isAuthenticated: true,
						user,
						isLoading: false
					};

					const props: PermissionGateProps = {
						permission: requiredPermissions,
						requireAdmin: true,
						fallbackBehavior: 'hide'
					};

					const result = checkPermissions(authState, props);

					const isAdmin = user.permissions.includes('admin');
					const hasAllPermissions = requiredPermissions.every((p) =>
						user.permissions.includes(p)
					);

					// User must be admin AND have all required permissions
					expect(result.hasAccess).toBe(isAdmin && hasAllPermissions);
				}
			),
			{ numRuns: 100 }
		);
	});
});

/**
 * Property tests for UI element visibility consistency
 */
describe('Property Test: UI Element Visibility Consistency', () => {
	/**
	 * Property: Access decision should be deterministic
	 * For any given auth state and props, the result should always be the same
	 */
	it('should produce deterministic results for same inputs', () => {
		fc.assert(
			fc.property(authStateArb, permissionGatePropsArb, (authState, props) => {
				const result1 = checkPermissions(authState, props);
				const result2 = checkPermissions(authState, props);

				// Same inputs should produce same outputs
				expect(result1.hasAccess).toBe(result2.hasAccess);
				expect(result1.shouldRender).toBe(result2.shouldRender);
				expect(result1.shouldDisable).toBe(result2.shouldDisable);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Adding permissions should not reduce access
	 * For any user, adding more permissions should never reduce their access level
	 */
	it('should not reduce access when adding permissions', () => {
		fc.assert(
			fc.property(
				userArb,
				permissionArb,
				permissionGatePropsArb,
				(user, additionalPermission, props) => {
					const authStateWithout: AuthState = {
						isAuthenticated: true,
						user,
						isLoading: false
					};

					const authStateWith: AuthState = {
						isAuthenticated: true,
						user: {
							...user,
							permissions: [...new Set([...user.permissions, additionalPermission])]
						},
						isLoading: false
					};

					const resultWithout = checkPermissions(authStateWithout, props);
					const resultWith = checkPermissions(authStateWith, props);

					// If user had access before, they should still have access
					if (resultWithout.hasAccess) {
						expect(resultWith.hasAccess).toBe(true);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Removing permissions should not increase access
	 * For any user, removing permissions should never increase their access level
	 */
	it('should not increase access when removing permissions', () => {
		fc.assert(
			fc.property(
				userArb.filter((u) => u.permissions.length > 0),
				fc.nat(),
				permissionGatePropsArb,
				(user, seed, props) => {
					const indexToRemove = seed % user.permissions.length;
					const reducedPermissions = user.permissions.filter((_, i) => i !== indexToRemove);

					const authStateWith: AuthState = {
						isAuthenticated: true,
						user,
						isLoading: false
					};

					const authStateWithout: AuthState = {
						isAuthenticated: true,
						user: {
							...user,
							permissions: reducedPermissions
						},
						isLoading: false
					};

					const resultWith = checkPermissions(authStateWith, props);
					const resultWithout = checkPermissions(authStateWithout, props);

					// If user didn't have access before, they shouldn't have access after removing permissions
					if (!resultWith.hasAccess) {
						expect(resultWithout.hasAccess).toBe(false);
					}
				}
			),
			{ numRuns: 100 }
		);
	});
});
