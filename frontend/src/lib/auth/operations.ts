// src/lib/auth/operations.ts
// Auth operations: login, logout, checkAuth
// Követelmények: 3.1, 3.4, 8.3, 8.4

import {
	type User,
	type LoginCredentials,
	type AuthResponse,
	authStateHelpers
} from './store.svelte';

/**
 * API base URL for the PHP proxy
 * In production, this should be configured via environment variables
 */
const API_BASE = '/api';

/**
 * CSRF token storage
 */
let csrfToken: string | null = null;

/**
 * Get the current CSRF token
 */
export function getCsrfToken(): string | null {
	return csrfToken;
}

/**
 * Set the CSRF token
 */
export function setCsrfToken(token: string | null): void {
	csrfToken = token;
}

/**
 * Make an authenticated API request
 * Handles 401 responses by clearing auth state
 * Követelmények: 4.5
 */
async function apiRequest<T>(
	endpoint: string,
	options: RequestInit = {}
): Promise<{ success: boolean; data?: T; error?: { code: string; message: string } }> {
	const headers: HeadersInit = {
		'Content-Type': 'application/json',
		...(options.headers || {})
	};

	// Add CSRF token for state-changing requests
	if (csrfToken && options.method && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(options.method)) {
		(headers as Record<string, string>)['X-CSRF-Token'] = csrfToken;
	}

	try {
		const response = await fetch(`${API_BASE}${endpoint}`, {
			...options,
			headers,
			credentials: 'include' // Include cookies for session
		});

		// Handle 401 Unauthorized - session expired
		// Követelmények: 4.5
		if (response.status === 401) {
			// Clear auth state
			authStateHelpers.clearAuth();
			setCsrfToken(null);

			// Redirect to login with current URL as redirect parameter
			if (typeof window !== 'undefined') {
				const currentPath = window.location.pathname + window.location.search;
				const loginUrl = `/login?redirect=${encodeURIComponent(currentPath)}`;
				window.location.href = loginUrl;
			}

			return {
				success: false,
				error: {
					code: 'UNAUTHORIZED',
					message: 'Session expired. Please log in again.'
				}
			};
		}

		const data = await response.json();
		return data;
	} catch (error) {
		return {
			success: false,
			error: {
				code: 'NETWORK_ERROR',
				message: error instanceof Error ? error.message : 'Network error occurred'
			}
		};
	}
}

/**
 * Login with email and password
 * Követelmények: 3.1
 *
 * @param credentials - Login credentials (email, password)
 * @returns AuthResponse with user data on success
 */
export async function login(credentials: LoginCredentials): Promise<AuthResponse> {
	authStateHelpers.setLoading(true);

	try {
		const response = await apiRequest<{ user: User; csrf_token: string }>('/auth/login', {
			method: 'POST',
			body: JSON.stringify(credentials)
		});

		if (response.success && response.data?.user) {
			// Store CSRF token
			if (response.data.csrf_token) {
				setCsrfToken(response.data.csrf_token);
			}

			// Update auth state with user
			authStateHelpers.setUser(response.data.user);
			authStateHelpers.setLoading(false);

			return {
				success: true,
				user: response.data.user
			};
		}

		// Login failed
		authStateHelpers.clearAuth();
		return {
			success: false,
			error: response.error || {
				code: 'AUTH_FAILED',
				message: 'Authentication failed'
			}
		};
	} catch (error) {
		authStateHelpers.clearAuth();
		return {
			success: false,
			error: {
				code: 'NETWORK_ERROR',
				message: error instanceof Error ? error.message : 'Network error occurred'
			}
		};
	}
}

/**
 * Logout the current user
 * Követelmények: 3.4
 *
 * @returns AuthResponse indicating success or failure
 */
export async function logout(): Promise<AuthResponse> {
	authStateHelpers.setLoading(true);

	try {
		const response = await apiRequest<{ message: string }>('/auth/logout', {
			method: 'POST'
		});

		// Clear auth state regardless of response
		authStateHelpers.clearAuth();
		setCsrfToken(null);

		if (response.success) {
			return { success: true };
		}

		return {
			success: false,
			error: response.error || {
				code: 'LOGOUT_FAILED',
				message: 'Logout failed'
			}
		};
	} catch (error) {
		// Clear auth state even on error
		authStateHelpers.clearAuth();
		setCsrfToken(null);

		return {
			success: false,
			error: {
				code: 'NETWORK_ERROR',
				message: error instanceof Error ? error.message : 'Network error occurred'
			}
		};
	}
}

/**
 * Check current authentication status
 * Követelmények: 8.3, 8.4
 *
 * Should be called when the frontend loads to verify auth state with the server
 *
 * @returns AuthResponse with current user data if authenticated
 */
export async function checkAuth(): Promise<AuthResponse> {
	authStateHelpers.setLoading(true);

	try {
		const response = await apiRequest<{
			authenticated: boolean;
			user: User | null;
			csrf_token: string;
		}>('/auth/status', {
			method: 'GET'
		});

		if (response.success && response.data) {
			// Store CSRF token
			if (response.data.csrf_token) {
				setCsrfToken(response.data.csrf_token);
			}

			if (response.data.authenticated && response.data.user) {
				authStateHelpers.setUser(response.data.user);
				authStateHelpers.setLoading(false);

				return {
					success: true,
					user: response.data.user
				};
			}
		}

		// Not authenticated or error
		authStateHelpers.clearAuth();
		return {
			success: false,
			error: response.error || {
				code: 'NOT_AUTHENTICATED',
				message: 'Not authenticated'
			}
		};
	} catch (error) {
		authStateHelpers.clearAuth();
		return {
			success: false,
			error: {
				code: 'NETWORK_ERROR',
				message: error instanceof Error ? error.message : 'Network error occurred'
			}
		};
	}
}

/**
 * Fetch fresh user data from the server
 * Useful for refreshing user permissions after changes
 *
 * @returns AuthResponse with updated user data
 */
export async function refreshUser(): Promise<AuthResponse> {
	try {
		const response = await apiRequest<{ user: User }>('/auth/me', {
			method: 'GET'
		});

		if (response.success && response.data?.user) {
			authStateHelpers.setUser(response.data.user);

			return {
				success: true,
				user: response.data.user
			};
		}

		return {
			success: false,
			error: response.error || {
				code: 'FETCH_FAILED',
				message: 'Failed to fetch user data'
			}
		};
	} catch (error) {
		return {
			success: false,
			error: {
				code: 'NETWORK_ERROR',
				message: error instanceof Error ? error.message : 'Network error occurred'
			}
		};
	}
}
