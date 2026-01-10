// Property test: API kliens egységesség
// **Tulajdonság 7: API Kliens Egységesség**
// **Validálja: Követelmények 7.1**
//
// *Bármely* HTTP metódus (GET, POST, PUT, DELETE, PATCH) esetén,
// az API kliens-nek ugyanazt a válasz formátumot kell visszaadnia (ApiResponse<T>).

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import * as fc from 'fast-check';

// Mock modules BEFORE importing the client
vi.mock('../auth/store.svelte', () => ({
	authStateHelpers: {
		clearAuth: vi.fn(),
		setLoading: vi.fn(),
		setUser: vi.fn()
	}
}));

vi.mock('../auth/operations', () => ({
	getCsrfToken: vi.fn(() => 'test-csrf-token'),
	setCsrfToken: vi.fn()
}));

// Import after mocks are set up
import { api, type ApiResponse } from './client';

// Mock fetch globally
const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

// Mock window.location for redirect handling
vi.stubGlobal('window', {
	location: {
		pathname: '/test',
		search: '',
		href: ''
	}
});

/**
 * Helper to check if a response conforms to ApiResponse<T> structure
 */
function isValidApiResponse<T>(response: unknown): response is ApiResponse<T> {
	if (typeof response !== 'object' || response === null) {
		return false;
	}

	const resp = response as Record<string, unknown>;

	// Must have 'success' boolean property
	if (typeof resp.success !== 'boolean') {
		return false;
	}

	// If success is false, error must have proper structure
	if (resp.success === false && resp.error !== undefined) {
		const error = resp.error as Record<string, unknown>;
		// Error must have 'code' and 'message' strings
		if (typeof error.code !== 'string' || typeof error.message !== 'string') {
			return false;
		}
	}

	return true;
}

/**
 * Arbitrary for generating valid API endpoints
 */
const endpointArb = fc.stringMatching(/^\/[a-z0-9\-\/]*$/).filter(s => s.length > 0 && s.length < 100);

/**
 * Arbitrary for generating mock API responses
 */
const mockApiResponseArb = fc.oneof(
	// Success response
	fc.record({
		success: fc.constant(true),
		data: fc.record({
			id: fc.string(),
			message: fc.string()
		})
	}),
	// Error response
	fc.record({
		success: fc.constant(false),
		error: fc.record({
			code: fc.constantFrom('VALIDATION_ERROR', 'NOT_FOUND', 'SERVER_ERROR'),
			message: fc.string()
		})
	})
);

/**
 * Arbitrary for HTTP status codes (excluding 401 which triggers redirect)
 */
const httpStatusArb = fc.constantFrom(200, 201, 400, 403, 404, 422, 500);

describe('Property Test: API Client Uniformity', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	afterEach(() => {
		vi.restoreAllMocks();
	});

	/**
	 * Property 7: API Kliens Egységesség
	 * For any HTTP method, the API client must return the same response format (ApiResponse<T>)
	 */
	it('should return ApiResponse<T> format for all HTTP methods with successful responses', async () => {
		await fc.assert(
			fc.asyncProperty(
				endpointArb,
				mockApiResponseArb,
				async (endpoint, mockResponse) => {
					// Setup mock fetch to return the mock response
					mockFetch.mockResolvedValue({
						ok: true,
						status: 200,
						json: async () => mockResponse
					});

					// Test all HTTP methods
					const methods = ['get', 'post', 'put', 'patch', 'delete'] as const;

					for (const method of methods) {
						let response: ApiResponse<unknown>;

						if (method === 'get') {
							response = await api.get(endpoint);
						} else if (method === 'delete') {
							response = await api.delete(endpoint);
						} else {
							response = await api[method](endpoint, { test: 'data' });
						}

						// Verify response conforms to ApiResponse<T> structure
						expect(isValidApiResponse(response)).toBe(true);
						expect(typeof response.success).toBe('boolean');

						if (response.success) {
							// Success response may have data
							expect(response.error).toBeUndefined();
						} else {
							// Error response must have error object with code and message
							expect(response.error).toBeDefined();
							expect(typeof response.error?.code).toBe('string');
							expect(typeof response.error?.message).toBe('string');
						}
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	it('should return ApiResponse<T> format for all HTTP methods with error responses', async () => {
		await fc.assert(
			fc.asyncProperty(
				endpointArb,
				httpStatusArb.filter(s => s >= 400),
				fc.string(),
				async (endpoint, status, errorMessage) => {
					// Setup mock fetch to return error response
					mockFetch.mockResolvedValue({
						ok: false,
						status,
						statusText: errorMessage,
						json: async () => ({
							success: false,
							error: {
								code: 'TEST_ERROR',
								message: errorMessage
							}
						})
					});

					// Test all HTTP methods
					const methods = ['get', 'post', 'put', 'patch', 'delete'] as const;

					for (const method of methods) {
						let response: ApiResponse<unknown>;

						if (method === 'get') {
							response = await api.get(endpoint);
						} else if (method === 'delete') {
							response = await api.delete(endpoint);
						} else {
							response = await api[method](endpoint, { test: 'data' });
						}

						// Verify response conforms to ApiResponse<T> structure
						expect(isValidApiResponse(response)).toBe(true);
						expect(response.success).toBe(false);
						expect(response.error).toBeDefined();
						expect(typeof response.error?.code).toBe('string');
						expect(typeof response.error?.message).toBe('string');
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	it('should return ApiResponse<T> format for all HTTP methods with network errors', async () => {
		await fc.assert(
			fc.asyncProperty(
				endpointArb,
				fc.string().filter(s => s.length > 0),
				async (endpoint, errorMessage) => {
					// Setup mock fetch to throw network error
					mockFetch.mockRejectedValue(new Error(errorMessage));

					// Test all HTTP methods
					const methods = ['get', 'post', 'put', 'patch', 'delete'] as const;

					for (const method of methods) {
						let response: ApiResponse<unknown>;

						if (method === 'get') {
							response = await api.get(endpoint);
						} else if (method === 'delete') {
							response = await api.delete(endpoint);
						} else {
							response = await api[method](endpoint, { test: 'data' });
						}

						// Verify response conforms to ApiResponse<T> structure
						expect(isValidApiResponse(response)).toBe(true);
						expect(response.success).toBe(false);
						expect(response.error).toBeDefined();
						expect(response.error?.code).toBe('NETWORK_ERROR');
						expect(typeof response.error?.message).toBe('string');
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	it('should return ApiResponse<T> format for all HTTP methods with non-JSON responses', async () => {
		await fc.assert(
			fc.asyncProperty(
				endpointArb,
				httpStatusArb,
				async (endpoint, status) => {
					// Setup mock fetch to return non-JSON response
					mockFetch.mockResolvedValue({
						ok: status < 400,
						status,
						statusText: 'Test Status',
						json: async () => {
							throw new Error('Invalid JSON');
						}
					});

					// Test all HTTP methods
					const methods = ['get', 'post', 'put', 'patch', 'delete'] as const;

					for (const method of methods) {
						let response: ApiResponse<unknown>;

						if (method === 'get') {
							response = await api.get(endpoint);
						} else if (method === 'delete') {
							response = await api.delete(endpoint);
						} else {
							response = await api[method](endpoint, { test: 'data' });
						}

						// Verify response conforms to ApiResponse<T> structure
						expect(isValidApiResponse(response)).toBe(true);
						expect(response.success).toBe(false);
						expect(response.error).toBeDefined();
						expect(typeof response.error?.code).toBe('string');
						expect(typeof response.error?.message).toBe('string');
					}
				}
			),
			{ numRuns: 100 }
		);
	});
});
