// src/lib/auth/store.svelte.ts
// Auth store implementation using Svelte 5 runes

/**
 * User interface representing authenticated user data
 */
export interface User {
	id: string;
	email: string;
	name: string;
	permissions: string[];
}

/**
 * AuthState interface for tracking authentication status
 */
export interface AuthState {
	isAuthenticated: boolean;
	user: User | null;
	isLoading: boolean;
}

/**
 * Login credentials interface
 */
export interface LoginCredentials {
	email: string;
	password: string;
}

/**
 * API response interface for auth operations
 */
export interface AuthResponse {
	success: boolean;
	user?: User;
	error?: {
		code: string;
		message: string;
	};
}

// Auth state using Svelte 5 $state rune
let authState = $state<AuthState>({
	isAuthenticated: false,
	user: null,
	isLoading: true
});

/**
 * Check if the current user has admin permission
 * @returns boolean indicating if user is admin
 */
export function getIsAdmin(): boolean {
	return authState.user?.permissions.includes('admin') ?? false;
}

/**
 * Check if the current user has a specific permission
 * @param permission - The permission to check
 * @returns boolean indicating if user has the permission
 */
export function hasPermission(permission: string): boolean {
	return authState.user?.permissions.includes(permission) ?? false;
}

/**
 * Get the current auth state (read-only)
 */
export function getAuthState(): Readonly<AuthState> {
	return authState;
}

/**
 * Get the current user (read-only)
 */
export function getUser(): User | null {
	return authState.user;
}

/**
 * Check if user is authenticated
 */
export function isAuthenticated(): boolean {
	return authState.isAuthenticated;
}

/**
 * Check if auth state is loading
 */
export function isLoading(): boolean {
	return authState.isLoading;
}

/**
 * Set loading state
 */
function setLoading(loading: boolean): void {
	authState.isLoading = loading;
}

/**
 * Set authenticated user
 */
function setUser(user: User | null): void {
	authState.user = user;
	authState.isAuthenticated = user !== null;
}

/**
 * Clear auth state (used on logout or auth failure)
 */
function clearAuth(): void {
	authState.user = null;
	authState.isAuthenticated = false;
	authState.isLoading = false;
}

// Export internal functions for use in auth operations
export const authStateHelpers = {
	setLoading,
	setUser,
	clearAuth
};
