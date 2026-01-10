// src/lib/api/index.ts
// API module exports

export { api, default as apiClient, ErrorCode } from './client';
export type { ApiResponse, ApiError, RequestOptions, ProgressCallback } from './client';

// Error handling utilities
export {
	getErrorMessage,
	isAuthError,
	isPermissionError,
	isValidationError,
	isNetworkError,
	isServerError,
	isRateLimited,
	getFieldErrors,
	hasFieldError,
	ApiErrorHandler
} from './errors';
