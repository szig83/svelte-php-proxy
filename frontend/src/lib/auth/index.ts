// src/lib/auth/index.ts
// Auth module exports

// Re-export types and state from store
export type { User, AuthState, LoginCredentials, AuthResponse } from './store.svelte';
export {
	getIsAdmin,
	hasPermission,
	getAuthState,
	getUser,
	isAuthenticated,
	isLoading
} from './store.svelte';

// Re-export operations
export { login, logout, checkAuth, refreshUser, getCsrfToken, setCsrfToken } from './operations';

// Re-export guard functions
export type { GuardOptions } from './guard.svelte';
export {
	guardRoute,
	createGuardState,
	getRedirectUrl,
	navigateAfterLogin
} from './guard.svelte';
