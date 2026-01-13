// src/lib/auth/guard.ts
// Route guard implementation for protected routes
// Követelmények: 5.2, 5.5

import { goto } from '$app/navigation';
import { browser } from '$app/environment';
// Direct imports to avoid circular dependency with index.ts
import { getAuthState } from './store.svelte';
import { checkAuth } from './operations';

/**
 * Guard options for route protection
 */
export interface GuardOptions {
	/** Required permission(s) to access the route */
	requiredPermissions?: string[];
	/** Require admin permission */
	requireAdmin?: boolean;
	/** Custom redirect URL (default: /login) */
	redirectTo?: string;
	/** Whether to preserve the original URL for redirect after login */
	preserveRedirect?: boolean;
}

/**
 * Default guard options
 */
const defaultOptions: GuardOptions = {
	redirectTo: '/login',
	preserveRedirect: true
};

/**
 * Build the redirect URL with optional return path
 * @param redirectTo - Base redirect URL
 * @param returnPath - Path to return to after login
 * @param preserveRedirect - Whether to include the return path
 */
function buildRedirectUrl(
	redirectTo: string,
	returnPath: string,
	preserveRedirect: boolean
): string {
	if (!preserveRedirect || returnPath === redirectTo || returnPath === '/login') {
		return redirectTo;
	}
	return `${redirectTo}?redirect=${encodeURIComponent(returnPath)}`;
}

/**
 * Check if user has all required permissions
 * @param userPermissions - User's current permissions
 * @param requiredPermissions - Required permissions to check
 */
function hasRequiredPermissions(
	userPermissions: string[],
	requiredPermissions: string[]
): boolean {
	return requiredPermissions.every((perm) => userPermissions.includes(perm));
}

/**
 * Route guard function to protect routes
 * Should be called in +layout.svelte or +page.svelte onMount
 *
 * @param options - Guard configuration options
 * @returns Promise<boolean> - true if access granted, false if redirected
 */
export async function guardRoute(options: GuardOptions = {}): Promise<boolean> {
	// Only run in browser
	if (!browser) {
		return true;
	}

	const opts = { ...defaultOptions, ...options };
	const currentPath = window.location.pathname;

	// Get current auth state
	let authState = getAuthState();

	// If still loading, wait for auth check
	if (authState.isLoading) {
		await checkAuth();
		authState = getAuthState();
	}

	// Check if authenticated
	if (!authState.isAuthenticated || !authState.user) {
		const redirectUrl = buildRedirectUrl(
			opts.redirectTo!,
			currentPath,
			opts.preserveRedirect!
		);
		await goto(redirectUrl, { replaceState: true });
		return false;
	}

	// Check admin requirement
	if (opts.requireAdmin && !authState.user.permissions.includes('admin')) {
		// Redirect to forbidden or home page
		await goto('/', { replaceState: true });
		return false;
	}

	// Check required permissions
	if (opts.requiredPermissions && opts.requiredPermissions.length > 0) {
		if (!hasRequiredPermissions(authState.user.permissions, opts.requiredPermissions)) {
			// Redirect to forbidden or home page
			await goto('/', { replaceState: true });
			return false;
		}
	}

	return true;
}

/**
 * Reactive guard state for use in Svelte components
 * Returns an object with guard status
 */
export function createGuardState() {
	let isChecking = $state(true);
	let isAllowed = $state(false);

	return {
		get isChecking() {
			return isChecking;
		},
		get isAllowed() {
			return isAllowed;
		},
		setChecking(value: boolean) {
			isChecking = value;
		},
		setAllowed(value: boolean) {
			isAllowed = value;
		}
	};
}

/**
 * Parse redirect URL from query parameters
 * @returns The redirect URL or null if not present
 */
export function getRedirectUrl(): string | null {
	if (!browser) return null;

	const params = new URLSearchParams(window.location.search);
	const redirect = params.get('redirect');

	// Validate redirect URL (must be relative path)
	if (redirect && redirect.startsWith('/') && !redirect.startsWith('//')) {
		return redirect;
	}

	return null;
}

/**
 * Navigate to the redirect URL or default path after successful login
 * @param defaultPath - Default path if no redirect URL is present
 */
export async function navigateAfterLogin(defaultPath: string = '/'): Promise<void> {
	if (!browser) return;

	const redirectUrl = getRedirectUrl();
	await goto(redirectUrl || defaultPath, { replaceState: true });
}
