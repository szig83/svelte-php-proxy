// Property test: Védett útvonal átirányítás és Hierarchikus route védelem
// **Tulajdonság 4: Védett Útvonal Átirányítás**
// **Validálja: Követelmények 5.2, 5.5**
//
// *Bármely* védett útvonalra történő navigáció esetén, ha a felhasználó nincs autentikálva,
// a Frontend-nek át kell irányítania a bejelentkezési oldalra, megőrizve a célzott URL-t.
//
// **Tulajdonság 5: Hierarchikus Route Védelem**
// **Validálja: Követelmények 5.1, 5.4**
//
// *Bármely* route prefix esetén, amely védettként van jelölve (pl. /admin), az összes alatta
// lévő útvonalnak (pl. /admin/users, /admin/stats) szintén védettnek kell lennie.

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import * as fc from 'fast-check';

// Track goto calls
let gotoCallArgs: { url: string; options?: { replaceState?: boolean } }[] = [];

// Mock $app/navigation
vi.mock('$app/navigation', () => ({
	goto: vi.fn(async (url: string, options?: { replaceState?: boolean }) => {
		gotoCallArgs.push({ url, options });
		return Promise.resolve();
	})
}));

// Mock $app/environment
vi.mock('$app/environment', () => ({
	browser: true
}));

// Auth state mock
let mockAuthState = {
	isAuthenticated: false,
	user: null as { id: string; email: string; name: string; permissions: string[] } | null,
	isLoading: false
};

// Mock store.svelte module (direct import in guard.ts)
vi.mock('./store.svelte', () => ({
	getAuthState: () => mockAuthState
}));

// Mock operations module (direct import in guard.ts)
vi.mock('./operations', () => ({
	checkAuth: vi.fn(async () => {
		mockAuthState.isLoading = false;
	})
}));

// Import after mocks
import { guardRoute } from './guard';

/**
 * Arbitrary for generating valid URL paths
 * Paths must start with / and contain valid URL characters
 */
const validPathArb = fc
	.array(fc.stringMatching(/^[a-z0-9\-_]+$/), { minLength: 1, maxLength: 5 })
	.map(segments => '/' + segments.join('/'))
	.filter(path => path.length > 1 && path.length < 200);

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
	'posts:edit'
);

/**
 * Arbitrary for generating user objects
 */
const userArb = fc.record({
	id: fc.uuid(),
	email: fc.emailAddress(),
	name: fc.string({ minLength: 1, maxLength: 50 }),
	permissions: fc.array(permissionArb, { minLength: 0, maxLength: 5 })
});

describe('Property Test: Protected Route Redirection', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		gotoCallArgs = [];
		// Reset auth state to unauthenticated
		mockAuthState = {
			isAuthenticated: false,
			user: null,
			isLoading: false
		};
	});

	afterEach(() => {
		vi.restoreAllMocks();
	});

	/**
	 * Property 4: Védett Útvonal Átirányítás
	 * For any protected route, if user is not authenticated,
	 * the system must redirect to login page with the original URL preserved
	 */
	it('should redirect unauthenticated users to login with preserved redirect URL', async () => {
		await fc.assert(
			fc.asyncProperty(validPathArb, async (targetPath) => {
				// Reset state for each test
				gotoCallArgs = [];
				mockAuthState = {
					isAuthenticated: false,
					user: null,
					isLoading: false
				};

				// Mock window.location.pathname
				vi.stubGlobal('window', {
					location: {
						pathname: targetPath,
						search: '',
						href: `http://localhost${targetPath}`
					}
				});

				// Call guard route
				const result = await guardRoute({ preserveRedirect: true });

				// Should return false (access denied)
				expect(result).toBe(false);

				// Should have called goto
				expect(gotoCallArgs.length).toBeGreaterThan(0);

				const lastCall = gotoCallArgs[gotoCallArgs.length - 1];

				// Should redirect to login
				expect(lastCall.url).toContain('/login');

				// Should preserve the target URL in redirect parameter
				// (unless target is /login itself)
				if (targetPath !== '/login') {
					expect(lastCall.url).toContain('redirect=');
					expect(lastCall.url).toContain(encodeURIComponent(targetPath));
				}

				// Should use replaceState
				expect(lastCall.options?.replaceState).toBe(true);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Authenticated users should be allowed access
	 * For any protected route, if user is authenticated,
	 * the system must allow access (return true)
	 */
	it('should allow authenticated users to access protected routes', async () => {
		await fc.assert(
			fc.asyncProperty(validPathArb, userArb, async (targetPath, user) => {
				// Reset state for each test
				gotoCallArgs = [];
				mockAuthState = {
					isAuthenticated: true,
					user,
					isLoading: false
				};

				// Mock window.location.pathname
				vi.stubGlobal('window', {
					location: {
						pathname: targetPath,
						search: '',
						href: `http://localhost${targetPath}`
					}
				});

				// Call guard route without permission requirements
				const result = await guardRoute();

				// Should return true (access granted)
				expect(result).toBe(true);

				// Should NOT have called goto (no redirect)
				expect(gotoCallArgs.length).toBe(0);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Redirect URL should be properly encoded
	 * For any target path with special characters, the redirect URL
	 * should be properly URL-encoded
	 */
	it('should properly encode redirect URLs with special characters', async () => {
		await fc.assert(
			fc.asyncProperty(
				fc.stringMatching(/^\/[a-z0-9\-_\/]+$/).filter(s => s.length > 1 && s.length < 100),
				async (targetPath) => {
					// Reset state
					gotoCallArgs = [];
					mockAuthState = {
						isAuthenticated: false,
						user: null,
						isLoading: false
					};

					vi.stubGlobal('window', {
						location: {
							pathname: targetPath,
							search: '',
							href: `http://localhost${targetPath}`
						}
					});

					await guardRoute({ preserveRedirect: true });

					if (gotoCallArgs.length > 0 && targetPath !== '/login') {
						const redirectUrl = gotoCallArgs[0].url;
						// The redirect parameter should be URL encoded
						const encodedPath = encodeURIComponent(targetPath);
						expect(redirectUrl).toContain(encodedPath);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Custom redirect URL should be respected
	 * For any custom redirect URL, the system should redirect to that URL
	 * instead of the default /login
	 */
	it('should respect custom redirect URL', async () => {
		const customRedirectPaths = ['/signin', '/auth/login', '/account/login'];

		await fc.assert(
			fc.asyncProperty(
				validPathArb,
				fc.constantFrom(...customRedirectPaths),
				async (targetPath, customRedirect) => {
					// Reset state
					gotoCallArgs = [];
					mockAuthState = {
						isAuthenticated: false,
						user: null,
						isLoading: false
					};

					vi.stubGlobal('window', {
						location: {
							pathname: targetPath,
							search: '',
							href: `http://localhost${targetPath}`
						}
					});

					await guardRoute({
						redirectTo: customRedirect,
						preserveRedirect: true
					});

					expect(gotoCallArgs.length).toBeGreaterThan(0);
					const redirectUrl = gotoCallArgs[0].url;

					// Should redirect to custom URL
					expect(redirectUrl).toContain(customRedirect);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: preserveRedirect=false should not include redirect parameter
	 */
	it('should not include redirect parameter when preserveRedirect is false', async () => {
		await fc.assert(
			fc.asyncProperty(validPathArb, async (targetPath) => {
				// Reset state
				gotoCallArgs = [];
				mockAuthState = {
					isAuthenticated: false,
					user: null,
					isLoading: false
				};

				vi.stubGlobal('window', {
					location: {
						pathname: targetPath,
						search: '',
						href: `http://localhost${targetPath}`
					}
				});

				await guardRoute({ preserveRedirect: false });

				expect(gotoCallArgs.length).toBeGreaterThan(0);
				const redirectUrl = gotoCallArgs[0].url;

				// Should NOT contain redirect parameter
				expect(redirectUrl).not.toContain('redirect=');
				expect(redirectUrl).toBe('/login');
			}),
			{ numRuns: 100 }
		);
	});
});

/**
 * Property 5: Hierarchikus Route Védelem
 * **Validálja: Követelmények 5.1, 5.4**
 *
 * *Bármely* route prefix esetén, amely védettként van jelölve (pl. /admin),
 * az összes alatta lévő útvonalnak (pl. /admin/users, /admin/stats) szintén védettnek kell lennie.
 */
describe('Property Test: Hierarchical Route Protection', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		gotoCallArgs = [];
		mockAuthState = {
			isAuthenticated: false,
			user: null,
			isLoading: false
		};
	});

	afterEach(() => {
		vi.restoreAllMocks();
	});

	/**
	 * Arbitrary for generating child paths under a protected prefix
	 * Generates paths like /admin/users, /admin/stats/daily, etc.
	 */
	const childPathSegmentArb = fc.stringMatching(/^[a-z0-9\-_]+$/).filter(s => s.length > 0 && s.length < 30);

	const protectedChildPathArb = (prefix: string) =>
		fc.array(childPathSegmentArb, { minLength: 1, maxLength: 4 })
			.map(segments => `${prefix}/${segments.join('/')}`);

	/**
	 * Property 5.1: All routes under /admin prefix require admin permission
	 * For any path under /admin, if user doesn't have admin permission,
	 * they should be denied access
	 */
	it('should deny access to all /admin child routes for non-admin users', async () => {
		await fc.assert(
			fc.asyncProperty(
				protectedChildPathArb('/admin'),
				userArb.filter(u => !u.permissions.includes('admin')),
				async (adminPath, nonAdminUser) => {
					// Reset state
					gotoCallArgs = [];
					mockAuthState = {
						isAuthenticated: true,
						user: nonAdminUser,
						isLoading: false
					};

					vi.stubGlobal('window', {
						location: {
							pathname: adminPath,
							search: '',
							href: `http://localhost${adminPath}`
						}
					});

					// Guard with admin requirement (as admin layout does)
					const result = await guardRoute({
						requireAdmin: true,
						redirectTo: '/login',
						preserveRedirect: true
					});

					// Should deny access
					expect(result).toBe(false);

					// Should redirect (to home for authenticated but unauthorized users)
					expect(gotoCallArgs.length).toBeGreaterThan(0);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 5.2: All routes under /admin prefix allow admin users
	 * For any path under /admin, if user has admin permission,
	 * they should be granted access
	 */
	it('should allow access to all /admin child routes for admin users', async () => {
		await fc.assert(
			fc.asyncProperty(
				protectedChildPathArb('/admin'),
				userArb.map(u => ({ ...u, permissions: [...u.permissions, 'admin'] })),
				async (adminPath, adminUser) => {
					// Reset state
					gotoCallArgs = [];
					mockAuthState = {
						isAuthenticated: true,
						user: adminUser,
						isLoading: false
					};

					vi.stubGlobal('window', {
						location: {
							pathname: adminPath,
							search: '',
							href: `http://localhost${adminPath}`
						}
					});

					// Guard with admin requirement
					const result = await guardRoute({
						requireAdmin: true,
						redirectTo: '/login',
						preserveRedirect: true
					});

					// Should allow access
					expect(result).toBe(true);

					// Should NOT redirect
					expect(gotoCallArgs.length).toBe(0);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 5.3: All routes under protected prefix require authentication
	 * For any path under a protected prefix, unauthenticated users should be redirected
	 */
	it('should deny access to all protected child routes for unauthenticated users', async () => {
		const protectedPrefixes = ['/dashboard', '/profile', '/settings', '/account'];

		await fc.assert(
			fc.asyncProperty(
				fc.constantFrom(...protectedPrefixes),
				fc.array(childPathSegmentArb, { minLength: 0, maxLength: 3 }),
				async (prefix, childSegments) => {
					const fullPath = childSegments.length > 0
						? `${prefix}/${childSegments.join('/')}`
						: prefix;

					// Reset state - unauthenticated
					gotoCallArgs = [];
					mockAuthState = {
						isAuthenticated: false,
						user: null,
						isLoading: false
					};

					vi.stubGlobal('window', {
						location: {
							pathname: fullPath,
							search: '',
							href: `http://localhost${fullPath}`
						}
					});

					// Guard without specific permissions (just auth check)
					const result = await guardRoute({
						redirectTo: '/login',
						preserveRedirect: true
					});

					// Should deny access
					expect(result).toBe(false);

					// Should redirect to login
					expect(gotoCallArgs.length).toBeGreaterThan(0);
					expect(gotoCallArgs[0].url).toContain('/login');
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 5.4: Hierarchical permission inheritance
	 * If a parent route requires certain permissions, all child routes
	 * should also require those permissions
	 */
	it('should enforce permission requirements on all child routes', async () => {
		const permissionRequirements = [
			{ prefix: '/users', permissions: ['users:manage'] as string[] },
			{ prefix: '/stats', permissions: ['stats:view'] as string[] },
			{ prefix: '/posts', permissions: ['posts:create', 'posts:edit'] as string[] }
		];

		await fc.assert(
			fc.asyncProperty(
				fc.constantFrom(...permissionRequirements),
				fc.array(childPathSegmentArb, { minLength: 0, maxLength: 3 }),
				userArb,
				async (requirement, childSegments, user) => {
					const fullPath = childSegments.length > 0
						? `${requirement.prefix}/${childSegments.join('/')}`
						: requirement.prefix;

					// Check if user has all required permissions
					const hasAllPermissions = requirement.permissions.every(
						(perm: string) => user.permissions.includes(perm)
					);

					// Reset state
					gotoCallArgs = [];
					mockAuthState = {
						isAuthenticated: true,
						user,
						isLoading: false
					};

					vi.stubGlobal('window', {
						location: {
							pathname: fullPath,
							search: '',
							href: `http://localhost${fullPath}`
						}
					});

					// Guard with required permissions
					const result = await guardRoute({
						requiredPermissions: requirement.permissions,
						redirectTo: '/login',
						preserveRedirect: true
					});

					// Result should match whether user has permissions
					expect(result).toBe(hasAllPermissions);

					// If denied, should have redirected
					if (!hasAllPermissions) {
						expect(gotoCallArgs.length).toBeGreaterThan(0);
					} else {
						expect(gotoCallArgs.length).toBe(0);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 5.5: Deep nesting maintains protection
	 * Routes at any depth under a protected prefix should maintain protection
	 */
	it('should maintain protection at any nesting depth', async () => {
		await fc.assert(
			fc.asyncProperty(
				fc.integer({ min: 1, max: 10 }),
				async (depth) => {
					// Generate a deeply nested admin path
					const segments = Array.from({ length: depth }, (_, i) => `level${i}`);
					const deepPath = `/admin/${segments.join('/')}`;

					// Reset state - authenticated but not admin
					gotoCallArgs = [];
					mockAuthState = {
						isAuthenticated: true,
						user: {
							id: 'test-user',
							email: 'test@example.com',
							name: 'Test User',
							permissions: ['read', 'write'] // No admin permission
						},
						isLoading: false
					};

					vi.stubGlobal('window', {
						location: {
							pathname: deepPath,
							search: '',
							href: `http://localhost${deepPath}`
						}
					});

					// Guard with admin requirement
					const result = await guardRoute({
						requireAdmin: true,
						redirectTo: '/login',
						preserveRedirect: true
					});

					// Should deny access regardless of depth
					expect(result).toBe(false);
					expect(gotoCallArgs.length).toBeGreaterThan(0);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 5.6: Protection applies uniformly to all child routes
	 * For any two child routes under the same protected prefix,
	 * the same protection rules should apply
	 */
	it('should apply uniform protection to sibling routes under same prefix', async () => {
		await fc.assert(
			fc.asyncProperty(
				protectedChildPathArb('/admin'),
				protectedChildPathArb('/admin'),
				userArb,
				async (path1, path2, user) => {
					const hasAdmin = user.permissions.includes('admin');

					// Test first path
					gotoCallArgs = [];
					mockAuthState = {
						isAuthenticated: true,
						user,
						isLoading: false
					};

					vi.stubGlobal('window', {
						location: {
							pathname: path1,
							search: '',
							href: `http://localhost${path1}`
						}
					});

					const result1 = await guardRoute({ requireAdmin: true });

					// Test second path
					gotoCallArgs = [];

					vi.stubGlobal('window', {
						location: {
							pathname: path2,
							search: '',
							href: `http://localhost${path2}`
						}
					});

					const result2 = await guardRoute({ requireAdmin: true });

					// Both paths should have the same access result
					expect(result1).toBe(result2);
					expect(result1).toBe(hasAdmin);
				}
			),
			{ numRuns: 100 }
		);
	});
});
