// src/lib/api/client.ts
// Egységes API kliens a PHP proxy-val való kommunikációhoz
// Követelmények: 7.1, 7.2, 7.3, 7.4, 7.5, 4.5

import { getCsrfToken, setCsrfToken } from '../auth/operations';
import { authStateHelpers } from '../auth/store.svelte';

/**
 * API base URL for the PHP proxy
 */
const API_BASE = '/api';

/**
 * Error codes for API responses
 */
export enum ErrorCode {
	NETWORK_ERROR = 'NETWORK_ERROR',
	UNAUTHORIZED = 'UNAUTHORIZED',
	FORBIDDEN = 'FORBIDDEN',
	NOT_FOUND = 'NOT_FOUND',
	VALIDATION_ERROR = 'VALIDATION_ERROR',
	SERVER_ERROR = 'SERVER_ERROR',
	CSRF_ERROR = 'CSRF_ERROR',
	RATE_LIMITED = 'RATE_LIMITED'
}

/**
 * API error interface
 */
export interface ApiError {
	code: string;
	message: string;
	details?: Record<string, string[]>;
}

/**
 * Standard API response interface
 */
export interface ApiResponse<T> {
	success: boolean;
	data?: T;
	error?: ApiError;
}

/**
 * Request options for the API client
 */
export interface RequestOptions {
	headers?: Record<string, string>;
	timeout?: number;
	skipCsrf?: boolean;
}

/**
 * File upload progress callback
 */
export type ProgressCallback = (progress: number) => void;

/**
 * Check if the HTTP method requires CSRF token
 */
function isStateChangingMethod(method: string): boolean {
	return ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase());
}

/**
 * Handle 401 Unauthorized response
 * Clears auth state and redirects to login
 */
function handleUnauthorized(): void {
	// Clear auth state
	authStateHelpers.clearAuth();
	setCsrfToken(null);

	// Redirect to login with current URL as redirect parameter
	if (typeof window !== 'undefined') {
		const currentPath = window.location.pathname + window.location.search;
		const loginUrl = `/login?redirect=${encodeURIComponent(currentPath)}`;
		window.location.href = loginUrl;
	}
}

/**
 * Map HTTP status code to error code
 */
function mapStatusToErrorCode(status: number): ErrorCode {
	switch (status) {
		case 401:
			return ErrorCode.UNAUTHORIZED;
		case 403:
			return ErrorCode.FORBIDDEN;
		case 404:
			return ErrorCode.NOT_FOUND;
		case 422:
			return ErrorCode.VALIDATION_ERROR;
		case 429:
			return ErrorCode.RATE_LIMITED;
		default:
			return status >= 500 ? ErrorCode.SERVER_ERROR : ErrorCode.VALIDATION_ERROR;
	}
}


/**
 * Core fetch wrapper with error handling and CSRF support
 */
async function request<T>(
	method: string,
	endpoint: string,
	body?: unknown,
	options: RequestOptions = {}
): Promise<ApiResponse<T>> {
	const headers: Record<string, string> = {
		...options.headers
	};

	// Add Content-Type for JSON requests (not for FormData)
	if (body && !(body instanceof FormData)) {
		headers['Content-Type'] = 'application/json';
	}

	// Add CSRF token for state-changing requests
	if (!options.skipCsrf && isStateChangingMethod(method)) {
		const csrfToken = getCsrfToken();
		if (csrfToken) {
			headers['X-CSRF-Token'] = csrfToken;
		}
	}

	const fetchOptions: RequestInit = {
		method: method.toUpperCase(),
		headers,
		credentials: 'include' // Include cookies for session
	};

	// Add body for non-GET requests
	if (body && method.toUpperCase() !== 'GET') {
		fetchOptions.body = body instanceof FormData ? body : JSON.stringify(body);
	}

	try {
		const response = await fetch(`${API_BASE}${endpoint}`, fetchOptions);

		// Handle 401 Unauthorized - redirect to login
		if (response.status === 401) {
			handleUnauthorized();
			return {
				success: false,
				error: {
					code: ErrorCode.UNAUTHORIZED,
					message: 'Session expired. Please log in again.'
				}
			};
		}

		// Parse response
		let data: ApiResponse<T>;
		try {
			data = await response.json();
		} catch {
			// If response is not JSON, create error response
			return {
				success: false,
				error: {
					code: mapStatusToErrorCode(response.status),
					message: response.statusText || 'An error occurred'
				}
			};
		}

		// Update CSRF token if provided in response
		if (data.data && typeof data.data === 'object' && 'csrf_token' in data.data) {
			setCsrfToken((data.data as Record<string, unknown>).csrf_token as string);
		}

		// Handle error responses
		if (!response.ok) {
			return {
				success: false,
				error: data.error || {
					code: mapStatusToErrorCode(response.status),
					message: 'An error occurred'
				}
			};
		}

		return data;
	} catch (error) {
		// Network error or other fetch failure
		return {
			success: false,
			error: {
				code: ErrorCode.NETWORK_ERROR,
				message: error instanceof Error ? error.message : 'Network error occurred'
			}
		};
	}
}

/**
 * API Client object with methods for all HTTP verbs
 */
export const api = {
	/**
	 * GET request
	 * @param endpoint - API endpoint (e.g., '/users')
	 * @param options - Request options
	 */
	async get<T>(endpoint: string, options?: RequestOptions): Promise<ApiResponse<T>> {
		return request<T>('GET', endpoint, undefined, options);
	},

	/**
	 * POST request
	 * @param endpoint - API endpoint
	 * @param data - Request body data
	 * @param options - Request options
	 */
	async post<T>(endpoint: string, data?: unknown, options?: RequestOptions): Promise<ApiResponse<T>> {
		return request<T>('POST', endpoint, data, options);
	},

	/**
	 * PUT request
	 * @param endpoint - API endpoint
	 * @param data - Request body data
	 * @param options - Request options
	 */
	async put<T>(endpoint: string, data?: unknown, options?: RequestOptions): Promise<ApiResponse<T>> {
		return request<T>('PUT', endpoint, data, options);
	},

	/**
	 * PATCH request
	 * @param endpoint - API endpoint
	 * @param data - Request body data
	 * @param options - Request options
	 */
	async patch<T>(endpoint: string, data?: unknown, options?: RequestOptions): Promise<ApiResponse<T>> {
		return request<T>('PATCH', endpoint, data, options);
	},

	/**
	 * DELETE request
	 * @param endpoint - API endpoint
	 * @param data - Optional request body data
	 * @param options - Request options
	 */
	async delete<T>(endpoint: string, data?: unknown, options?: RequestOptions): Promise<ApiResponse<T>> {
		return request<T>('DELETE', endpoint, data, options);
	},


	/**
	 * Upload files with optional progress tracking
	 * Követelmények: 7.5
	 *
	 * @param endpoint - API endpoint
	 * @param files - FileList or File array to upload
	 * @param data - Additional form data
	 * @param onProgress - Optional progress callback
	 * @param options - Request options
	 */
	async upload<T>(
		endpoint: string,
		files: FileList | File[],
		data?: Record<string, string | Blob>,
		onProgress?: ProgressCallback,
		options?: RequestOptions
	): Promise<ApiResponse<T>> {
		const formData = new FormData();

		// Add files to FormData
		const fileArray = files instanceof FileList ? Array.from(files) : files;
		fileArray.forEach((file, index) => {
			formData.append(`file${index}`, file, file.name);
		});

		// Add additional data
		if (data) {
			Object.entries(data).forEach(([key, value]) => {
				formData.append(key, value);
			});
		}

		// Add CSRF token to form data
		const csrfToken = getCsrfToken();
		if (csrfToken && !options?.skipCsrf) {
			formData.append('_csrf_token', csrfToken);
		}

		// If progress tracking is needed, use XMLHttpRequest
		if (onProgress) {
			return uploadWithProgress<T>(endpoint, formData, onProgress, options);
		}

		// Otherwise use standard fetch
		return request<T>('POST', endpoint, formData, { ...options, skipCsrf: true });
	}
};

/**
 * Upload with progress tracking using XMLHttpRequest
 */
function uploadWithProgress<T>(
	endpoint: string,
	formData: FormData,
	onProgress: ProgressCallback,
	options?: RequestOptions
): Promise<ApiResponse<T>> {
	return new Promise((resolve) => {
		const xhr = new XMLHttpRequest();

		// Track upload progress
		xhr.upload.addEventListener('progress', (event) => {
			if (event.lengthComputable) {
				const progress = Math.round((event.loaded / event.total) * 100);
				onProgress(progress);
			}
		});

		// Handle completion
		xhr.addEventListener('load', () => {
			if (xhr.status === 401) {
				handleUnauthorized();
				resolve({
					success: false,
					error: {
						code: ErrorCode.UNAUTHORIZED,
						message: 'Session expired. Please log in again.'
					}
				});
				return;
			}

			try {
				const data = JSON.parse(xhr.responseText);

				// Update CSRF token if provided
				if (data.data?.csrf_token) {
					setCsrfToken(data.data.csrf_token);
				}

				resolve(data);
			} catch {
				resolve({
					success: false,
					error: {
						code: mapStatusToErrorCode(xhr.status),
						message: xhr.statusText || 'Upload failed'
					}
				});
			}
		});

		// Handle errors
		xhr.addEventListener('error', () => {
			resolve({
				success: false,
				error: {
					code: ErrorCode.NETWORK_ERROR,
					message: 'Network error during upload'
				}
			});
		});

		xhr.addEventListener('abort', () => {
			resolve({
				success: false,
				error: {
					code: ErrorCode.NETWORK_ERROR,
					message: 'Upload was aborted'
				}
			});
		});

		// Open and send request
		xhr.open('POST', `${API_BASE}${endpoint}`);
		xhr.withCredentials = true;

		// Add custom headers
		if (options?.headers) {
			Object.entries(options.headers).forEach(([key, value]) => {
				xhr.setRequestHeader(key, value);
			});
		}

		// Add CSRF token header
		const csrfToken = getCsrfToken();
		if (csrfToken) {
			xhr.setRequestHeader('X-CSRF-Token', csrfToken);
		}

		xhr.send(formData);
	});
}

// Default export
export default api;
