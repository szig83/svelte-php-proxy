/**
 * Error Logger Module - Public API
 * Követelmény: 7.1 - THE Error_Logger SHALL biztosít egy API-t manuális hiba naplózáshoz
 */

// Types
export type {
	ErrorType,
	ErrorSeverity,
	ErrorContext,
	ErrorEntry,
	ErrorLoggerConfig,
	RateLimitConfig,
	QueuedError,
	ApiError,
	ErrorLogger,
	RateLimiter,
	RetryQueue
} from './types';

// Logger functions
export {
	getErrorLogger,
	initErrorLogger,
	logError,
	logWarning,
	logInfo,
	logApiError,
	logJsError
} from './logger';

// Rate Limiter
export { SlidingWindowRateLimiter } from './rate-limiter';

// Retry Queue
export { LocalStorageRetryQueue } from './retry-queue';
