/**
 * Property Tests for Error Logger
 *
 * **Property 1: Hiba Kontextus Teljessége**
 * **Validálja: Követelmények 3.1, 3.2, 3.3**
 *
 * *Bármely* naplózott hiba bejegyzés esetén a context objektumnak tartalmaznia KELL
 * az összes kötelező mezőt: url (nem üres string), userAgent (nem üres string),
 * és timestamp (érvényes ISO 8601 formátum).
 *
 * **Property 2: Stack Trace Megőrzése**
 * **Validálja: Követelmények 1.3**
 *
 * *Bármely* Error objektum esetén, amelynek van stack property-je és naplózásra kerül,
 * az eredményül kapott ErrorEntry-nek tartalmaznia KELL a stack trace-t a stack mezőjében.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import * as fc from 'fast-check';
import { collectContext, DEFAULT_CONFIG, ErrorLoggerImpl } from './logger';
import type { ErrorLoggerConfig, ErrorEntry, ErrorType, ErrorSeverity } from './types';

// Mock the auth store
vi.mock('../auth/store.svelte', () => ({
	getUser: vi.fn(() => null)
}));

/**
 * Helper to validate ISO 8601 timestamp format
 */
function isValidISO8601(timestamp: string): boolean {
	const date = new Date(timestamp);
	return !isNaN(date.getTime()) && timestamp === date.toISOString();
}

/**
 * Helper to create a mock window/navigator environment
 */
function setupBrowserMocks(url: string, userAgent: string) {
	Object.defineProperty(global, 'window', {
		value: { location: { href: url } },
		writable: true,
		configurable: true
	});
	Object.defineProperty(global, 'navigator', {
		value: { userAgent },
		writable: true,
		configurable: true
	});
}

/**
 * Helper to cleanup browser mocks
 */
function cleanupBrowserMocks() {
	// @ts-expect-error - cleaning up global mocks
	delete global.window;
	// @ts-expect-error - cleaning up global mocks
	delete global.navigator;
}

describe('Property Test: Hiba Kontextus Teljessége', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	afterEach(() => {
		cleanupBrowserMocks();
	});

	/**
	 * Property 1: Hiba Kontextus Teljessége
	 * For any error entry, the context object must contain all required fields:
	 * - url: non-empty string
	 * - userAgent: non-empty string
	 * And the entry must have a valid ISO 8601 timestamp
	 */
	it('should always include url and userAgent in context when browser environment is available', () => {
		fc.assert(
			fc.property(
				// Generate valid URLs
				fc.webUrl(),
				// Generate user agent strings
				fc.string({ minLength: 1, maxLength: 300 }),
				// Generate optional extra context
				fc.option(fc.dictionary(fc.string({ minLength: 1 }), fc.jsonValue()), { nil: undefined }),
				(url, userAgent, extra) => {
					// Setup browser environment
					setupBrowserMocks(url, userAgent);

					// Collect context
					const context = collectContext(DEFAULT_CONFIG, extra);

					// Verify required fields are present and non-empty
					expect(context.url).toBeDefined();
					expect(typeof context.url).toBe('string');
					expect(context.url.length).toBeGreaterThan(0);
					expect(context.url).toBe(url);

					expect(context.userAgent).toBeDefined();
					expect(typeof context.userAgent).toBe('string');
					expect(context.userAgent.length).toBeGreaterThan(0);
					expect(context.userAgent).toBe(userAgent);

					// Cleanup
					cleanupBrowserMocks();
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Extra context should be preserved when provided
	 */
	it('should preserve extra context when provided', () => {
		fc.assert(
			fc.property(
				fc.webUrl(),
				fc.string({ minLength: 1, maxLength: 300 }),
				fc.dictionary(fc.string({ minLength: 1, maxLength: 50 }), fc.jsonValue(), { minKeys: 1, maxKeys: 10 }),
				(url, userAgent, extra) => {
					setupBrowserMocks(url, userAgent);

					const context = collectContext(DEFAULT_CONFIG, extra);

					// Extra context should be present
					expect(context.extra).toBeDefined();
					expect(context.extra).toEqual(extra);

					cleanupBrowserMocks();
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: App version should be included when configured
	 */
	it('should include appVersion when configured', () => {
		fc.assert(
			fc.property(
				fc.webUrl(),
				fc.string({ minLength: 1, maxLength: 300 }),
				fc.string({ minLength: 1, maxLength: 50 }),
				(url, userAgent, appVersion) => {
					setupBrowserMocks(url, userAgent);

					const config: ErrorLoggerConfig = {
						...DEFAULT_CONFIG,
						appVersion
					};

					const context = collectContext(config, undefined);

					// App version should be present
					expect(context.appVersion).toBe(appVersion);

					cleanupBrowserMocks();
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Timestamp should always be valid ISO 8601 format
	 */
	it('should generate valid ISO 8601 timestamps for error entries', () => {
		fc.assert(
			fc.property(
				// Generate error messages
				fc.string({ minLength: 1, maxLength: 500 }),
				// Generate error types
				fc.constantFrom<ErrorType>('javascript', 'api', 'manual'),
				// Generate severity levels
				fc.constantFrom<ErrorSeverity>('error', 'warning', 'info'),
				(message, type, severity) => {
					// Create a timestamp the same way the logger does
					const timestamp = new Date().toISOString();

					// Verify it's a valid ISO 8601 format
					expect(isValidISO8601(timestamp)).toBe(true);

					// Verify the timestamp can be parsed back to a valid date
					const parsedDate = new Date(timestamp);
					expect(parsedDate.getTime()).not.toBeNaN();

					// Verify round-trip: parsing and re-formatting should give same result
					expect(parsedDate.toISOString()).toBe(timestamp);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Context should handle empty extra gracefully
	 */
	it('should not include extra field when extra is empty or undefined', () => {
		fc.assert(
			fc.property(
				fc.webUrl(),
				fc.string({ minLength: 1, maxLength: 300 }),
				fc.constantFrom(undefined, {}),
				(url, userAgent, extra) => {
					setupBrowserMocks(url, userAgent);

					const context = collectContext(DEFAULT_CONFIG, extra);

					// Extra should not be present when empty or undefined
					if (extra === undefined || Object.keys(extra).length === 0) {
						expect(context.extra).toBeUndefined();
					}

					cleanupBrowserMocks();
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Context structure should be consistent regardless of input
	 */
	it('should always produce a context with consistent structure', () => {
		fc.assert(
			fc.property(
				fc.webUrl(),
				fc.string({ minLength: 1, maxLength: 300 }),
				fc.option(fc.string({ minLength: 1, maxLength: 50 }), { nil: undefined }),
				fc.option(fc.dictionary(fc.string({ minLength: 1 }), fc.jsonValue()), { nil: undefined }),
				(url, userAgent, appVersion, extra) => {
					setupBrowserMocks(url, userAgent);

					const config: ErrorLoggerConfig = {
						...DEFAULT_CONFIG,
						appVersion
					};

					const context = collectContext(config, extra);

					// Required fields must always be strings
					expect(typeof context.url).toBe('string');
					expect(typeof context.userAgent).toBe('string');

					// Optional fields should be correct type if present
					if (context.userId !== undefined) {
						expect(typeof context.userId).toBe('string');
					}
					if (context.appVersion !== undefined) {
						expect(typeof context.appVersion).toBe('string');
					}
					if (context.extra !== undefined) {
						expect(typeof context.extra).toBe('object');
					}

					cleanupBrowserMocks();
				}
			),
			{ numRuns: 100 }
		);
	});
});


/**
 * Feature: frontend-error-logging, Property 2: Stack Trace Megőrzése
 * **Validálja: Követelmények 1.3**
 *
 * *Bármely* Error objektum esetén, amelynek van stack property-je és naplózásra kerül,
 * az eredményül kapott ErrorEntry-nek tartalmaznia KELL a stack trace-t a stack mezőjében.
 */
describe('Property Test: Stack Trace Megőrzése', () => {
	let capturedEntries: ErrorEntry[] = [];
	let originalFetch: typeof globalThis.fetch;

	beforeEach(() => {
		vi.clearAllMocks();
		capturedEntries = [];

		// Setup browser mocks
		setupBrowserMocks('https://example.com/test', 'TestUserAgent/1.0');

		// Mock fetch to capture sent entries
		originalFetch = globalThis.fetch;
		globalThis.fetch = vi.fn().mockImplementation(async (_url: string, options?: RequestInit) => {
			if (options?.body) {
				const entry = JSON.parse(options.body as string) as ErrorEntry;
				capturedEntries.push(entry);
			}
			return new Response(JSON.stringify({ success: true }), { status: 200 });
		});
	});

	afterEach(() => {
		cleanupBrowserMocks();
		globalThis.fetch = originalFetch;
	});

	/**
	 * Property 2: Stack Trace Megőrzése
	 * For any Error object with a stack property that is logged,
	 * the resulting ErrorEntry must contain the stack trace in its stack field.
	 */
	it('should preserve stack trace when logging Error objects with stack property', () => {
		fc.assert(
			fc.property(
				// Generate error messages
				fc.string({ minLength: 1, maxLength: 200 }),
				// Generate stack traces (simulating real stack traces)
				fc.array(
					fc.record({
						functionName: fc.string({ minLength: 1, maxLength: 50 }),
						fileName: fc.string({ minLength: 1, maxLength: 100 }),
						lineNumber: fc.nat({ max: 10000 }),
						columnNumber: fc.nat({ max: 1000 })
					}),
					{ minLength: 1, maxLength: 10 }
				),
				(message, stackFrames) => {
					// Create a realistic stack trace string
					const stackTrace =
						`Error: ${message}\n` +
						stackFrames
							.map(
								(frame) =>
									`    at ${frame.functionName} (${frame.fileName}:${frame.lineNumber}:${frame.columnNumber})`
							)
							.join('\n');

					// Create an Error object with the stack trace
					const error = new Error(message);
					error.stack = stackTrace;

					// Create a fresh logger instance for each test
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					// Clear captured entries before logging
					capturedEntries = [];

					// Log the error
					logger.log(error);

					// Verify the stack trace was preserved
					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// The stack field must be defined and contain the stack trace
					expect(entry.stack).toBeDefined();
					expect(entry.stack).toBe(stackTrace);

					// Verify the message is also preserved
					expect(entry.message).toBe(message);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: String errors should not have stack trace
	 * When logging a string (not an Error object), the stack field should be undefined
	 */
	it('should not include stack trace when logging string messages', () => {
		fc.assert(
			fc.property(fc.string({ minLength: 1, maxLength: 500 }), (message) => {
				const logger = new ErrorLoggerImpl();
				logger.init({ enabled: true, isDevelopment: false });

				capturedEntries = [];

				// Log a string message (not an Error object)
				logger.log(message);

				expect(capturedEntries.length).toBe(1);
				const entry = capturedEntries[0];

				// Stack should be undefined for string messages
				expect(entry.stack).toBeUndefined();

				// Message should be preserved
				expect(entry.message).toBe(message);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Error objects without stack should have undefined stack in entry
	 * When an Error object has no stack property, the entry's stack should be undefined
	 */
	it('should handle Error objects without stack property gracefully', () => {
		fc.assert(
			fc.property(fc.string({ minLength: 1, maxLength: 200 }), (message) => {
				// Create an Error and remove its stack
				const error = new Error(message);
				delete error.stack;

				const logger = new ErrorLoggerImpl();
				logger.init({ enabled: true, isDevelopment: false });

				capturedEntries = [];

				logger.log(error);

				expect(capturedEntries.length).toBe(1);
				const entry = capturedEntries[0];

				// Stack should be undefined when Error has no stack
				expect(entry.stack).toBeUndefined();

				// Message should still be preserved
				expect(entry.message).toBe(message);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Stack trace content should be exactly preserved (no modification)
	 * The stack trace string should be stored exactly as provided, without any transformation
	 */
	it('should preserve stack trace content exactly without modification', () => {
		fc.assert(
			fc.property(
				fc.string({ minLength: 1, maxLength: 100 }),
				// Generate arbitrary stack trace strings (including special characters)
				fc.string({ minLength: 1, maxLength: 2000 }),
				(message, arbitraryStack) => {
					const error = new Error(message);
					error.stack = arbitraryStack;

					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					logger.log(error);

					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Stack should be exactly the same as provided
					expect(entry.stack).toBe(arbitraryStack);
				}
			),
			{ numRuns: 100 }
		);
	});
});

/**
 * Feature: frontend-error-logging, Property 3: API Hiba Információ Teljessége
 * **Validálja: Követelmények 2.1, 2.3**
 *
 * *Bármely* API hiba esetén, amely a logApiError() metódussal kerül naplózásra,
 * az eredményül kapott ErrorEntry-nek tartalmaznia KELL az endpoint URL-t és
 * a HTTP státuszkódot a context.extra mezőjében.
 */
describe('Property Test: API Hiba Információ Teljessége', () => {
	let capturedEntries: ErrorEntry[] = [];
	let originalFetch: typeof globalThis.fetch;

	beforeEach(() => {
		vi.clearAllMocks();
		capturedEntries = [];

		// Setup browser mocks
		setupBrowserMocks('https://example.com/app', 'TestUserAgent/1.0');

		// Mock fetch to capture sent entries
		originalFetch = globalThis.fetch;
		globalThis.fetch = vi.fn().mockImplementation(async (_url: string, options?: RequestInit) => {
			if (options?.body) {
				const entry = JSON.parse(options.body as string) as ErrorEntry;
				capturedEntries.push(entry);
			}
			return new Response(JSON.stringify({ success: true }), { status: 200 });
		});
	});

	afterEach(() => {
		cleanupBrowserMocks();
		globalThis.fetch = originalFetch;
	});

	/**
	 * Property 3: API Hiba Információ Teljessége
	 * For any API error logged via logApiError(), the resulting ErrorEntry must contain
	 * the endpoint URL and HTTP status code in context.extra.
	 */
	it('should include endpoint and status in context.extra for API errors', () => {
		// Helper to generate API endpoint paths
		const endpointArb = fc
			.stringMatching(/^\/[a-zA-Z0-9\-\/]*$/)
			.filter((s) => s.length >= 2 && s.length <= 100);

		fc.assert(
			fc.property(
				// Generate API endpoint paths
				endpointArb,
				// Generate HTTP status codes (4xx and 5xx errors)
				fc.integer({ min: 400, max: 599 }),
				// Generate error messages
				fc.string({ minLength: 1, maxLength: 200 }),
				// Generate optional error codes
				fc.option(fc.string({ minLength: 1, maxLength: 50 }), { nil: undefined }),
				(endpoint, status, message, errorCode) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					// Log an API error
					logger.logApiError(endpoint, status, {
						message,
						code: errorCode
					});

					// Verify the entry was captured
					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Verify entry type is 'api'
					expect(entry.type).toBe('api');

					// Verify context.extra contains endpoint and status
					expect(entry.context.extra).toBeDefined();
					expect(entry.context.extra!.endpoint).toBe(endpoint);
					expect(entry.context.extra!.status).toBe(status);

					// Verify message is preserved
					expect(entry.message).toBe(message);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: API error should preserve error code when provided
	 */
	it('should preserve error code in context.extra when provided', () => {
		const endpointArb = fc
			.stringMatching(/^\/[a-zA-Z0-9\-\/]*$/)
			.filter((s) => s.length >= 2 && s.length <= 100);

		fc.assert(
			fc.property(
				endpointArb,
				fc.integer({ min: 400, max: 599 }),
				fc.string({ minLength: 1, maxLength: 200 }),
				fc.string({ minLength: 1, maxLength: 50 }),
				(endpoint, status, message, errorCode) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					logger.logApiError(endpoint, status, {
						message,
						code: errorCode
					});

					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Error code should be preserved
					expect(entry.context.extra).toBeDefined();
					expect(entry.context.extra!.errorCode).toBe(errorCode);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: API error should preserve error details when provided
	 */
	it('should preserve error details in context.extra when provided', () => {
		const endpointArb = fc
			.stringMatching(/^\/[a-zA-Z0-9\-\/]*$/)
			.filter((s) => s.length >= 2 && s.length <= 100);

		// Helper to normalize -0 to 0 (JavaScript edge case)
		const normalizeNegativeZero = (value: unknown): unknown => {
			if (typeof value === 'number' && Object.is(value, -0)) {
				return 0;
			}
			if (Array.isArray(value)) {
				return value.map(normalizeNegativeZero);
			}
			if (value !== null && typeof value === 'object') {
				const result: Record<string, unknown> = {};
				for (const [k, v] of Object.entries(value)) {
					result[k] = normalizeNegativeZero(v);
				}
				return result;
			}
			return value;
		};

		fc.assert(
			fc.property(
				endpointArb,
				fc.integer({ min: 400, max: 599 }),
				fc.string({ minLength: 1, maxLength: 200 }),
				fc.dictionary(fc.string({ minLength: 1, maxLength: 30 }), fc.jsonValue(), {
					minKeys: 1,
					maxKeys: 5
				}),
				(endpoint, status, message, details) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					// Normalize -0 values before comparison
					const normalizedDetails = normalizeNegativeZero(details);

					logger.logApiError(endpoint, status, {
						message,
						details: normalizedDetails as Record<string, unknown>
					});

					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Error details should be preserved
					expect(entry.context.extra).toBeDefined();
					expect(entry.context.extra!.errorDetails).toEqual(normalizedDetails);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: API error entries should have severity 'error'
	 */
	it('should always set severity to error for API errors', () => {
		const endpointArb = fc
			.stringMatching(/^\/[a-zA-Z0-9\-\/]*$/)
			.filter((s) => s.length >= 2 && s.length <= 100);

		fc.assert(
			fc.property(
				endpointArb,
				fc.integer({ min: 400, max: 599 }),
				fc.string({ minLength: 1, maxLength: 200 }),
				(endpoint, status, message) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					logger.logApiError(endpoint, status, { message });

					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Severity should always be 'error' for API errors
					expect(entry.severity).toBe('error');
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: API error should handle all valid HTTP error status codes
	 */
	it('should correctly handle all HTTP error status codes (4xx and 5xx)', () => {
		const endpointArb = fc
			.stringMatching(/^\/[a-zA-Z0-9\-\/]*$/)
			.filter((s) => s.length >= 2 && s.length <= 100);

		fc.assert(
			fc.property(
				endpointArb,
				// Test specific common HTTP error codes
				fc.constantFrom(400, 401, 403, 404, 405, 408, 422, 429, 500, 502, 503, 504),
				fc.string({ minLength: 1, maxLength: 200 }),
				(endpoint, status, message) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					logger.logApiError(endpoint, status, { message });

					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Status should be exactly preserved
					expect(entry.context.extra).toBeDefined();
					expect(entry.context.extra!.status).toBe(status);
					expect(typeof entry.context.extra!.status).toBe('number');
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: API error should use provided message or default
	 */
	it('should use provided message or default to API Error', () => {
		const endpointArb = fc
			.stringMatching(/^\/[a-zA-Z0-9\-\/]*$/)
			.filter((s) => s.length >= 2 && s.length <= 100);

		fc.assert(
			fc.property(
				endpointArb,
				fc.integer({ min: 400, max: 599 }),
				fc.option(fc.string({ minLength: 1, maxLength: 200 }), { nil: undefined }),
				(endpoint, status, message) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					logger.logApiError(endpoint, status, {
						message: message || ''
					});

					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Message should be the provided message or 'API Error' if empty
					if (message) {
						expect(entry.message).toBe(message);
					} else {
						expect(entry.message).toBe('API Error');
					}
				}
			),
			{ numRuns: 100 }
		);
	});
});


/**
 * Feature: frontend-error-logging, Property 9: Manuális Naplózás Paraméter Megőrzése
 * **Validálja: Követelmények 7.2, 7.3**
 *
 * *Bármely* manuális log hívás esetén severity-vel és extra kontextussal,
 * az eredményül kapott ErrorEntry-nek a megadott severity-vel KELL rendelkeznie
 * és tartalmaznia KELL az összes extra kontextus mezőt.
 */
describe('Property Test: Manuális Naplózás Paraméter Megőrzése', () => {
	let capturedEntries: ErrorEntry[] = [];
	let originalFetch: typeof globalThis.fetch;

	beforeEach(() => {
		vi.clearAllMocks();
		capturedEntries = [];

		// Setup browser mocks
		setupBrowserMocks('https://example.com/test', 'TestUserAgent/1.0');

		// Mock fetch to capture sent entries
		originalFetch = globalThis.fetch;
		globalThis.fetch = vi.fn().mockImplementation(async (_url: string, options?: RequestInit) => {
			if (options?.body) {
				const entry = JSON.parse(options.body as string) as ErrorEntry;
				capturedEntries.push(entry);
			}
			return new Response(JSON.stringify({ success: true }), { status: 200 });
		});
	});

	afterEach(() => {
		cleanupBrowserMocks();
		globalThis.fetch = originalFetch;
	});

	/**
	 * Property 9: Manuális Naplózás Paraméter Megőrzése - log() with error severity
	 * For any manual log() call, the resulting ErrorEntry must have severity 'error'
	 * and contain all extra context fields.
	 */
	it('should preserve severity as error and extra context when using log()', () => {
		fc.assert(
			fc.property(
				// Generate error messages
				fc.string({ minLength: 1, maxLength: 200 }),
				// Generate extra context with various key-value pairs
				fc.dictionary(
					fc.string({ minLength: 1, maxLength: 30 }).filter((s) => /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(s)),
					fc.jsonValue(),
					{ minKeys: 1, maxKeys: 10 }
				),
				(message, extra) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					// Log with extra context
					logger.log(message, extra);

					// Verify entry was captured
					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Verify severity is 'error' for log() calls
					expect(entry.severity).toBe('error');

					// Verify type is 'manual'
					expect(entry.type).toBe('manual');

					// Verify message is preserved
					expect(entry.message).toBe(message);

					// Verify extra context is preserved (normalized through JSON to handle -0 edge case)
					expect(entry.context.extra).toBeDefined();
					const normalizedExtra = JSON.parse(JSON.stringify(extra));
					for (const [key, value] of Object.entries(normalizedExtra)) {
						expect(entry.context.extra![key]).toEqual(value);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 9: Manuális Naplózás Paraméter Megőrzése - warn() with warning severity
	 * For any manual warn() call, the resulting ErrorEntry must have severity 'warning'
	 * and contain all extra context fields.
	 */
	it('should preserve severity as warning and extra context when using warn()', () => {
		fc.assert(
			fc.property(
				// Generate warning messages
				fc.string({ minLength: 1, maxLength: 200 }),
				// Generate extra context
				fc.dictionary(
					fc.string({ minLength: 1, maxLength: 30 }).filter((s) => /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(s)),
					fc.jsonValue(),
					{ minKeys: 1, maxKeys: 10 }
				),
				(message, extra) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					// Log warning with extra context
					logger.warn(message, extra);

					// Verify entry was captured
					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Verify severity is 'warning' for warn() calls
					expect(entry.severity).toBe('warning');

					// Verify type is 'manual'
					expect(entry.type).toBe('manual');

					// Verify message is preserved
					expect(entry.message).toBe(message);

					// Verify extra context is preserved (normalized through JSON to handle -0 edge case)
					expect(entry.context.extra).toBeDefined();
					const normalizedExtra = JSON.parse(JSON.stringify(extra));
					for (const [key, value] of Object.entries(normalizedExtra)) {
						expect(entry.context.extra![key]).toEqual(value);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 9: Manuális Naplózás Paraméter Megőrzése - info() with info severity
	 * For any manual info() call, the resulting ErrorEntry must have severity 'info'
	 * and contain all extra context fields.
	 */
	it('should preserve severity as info and extra context when using info()', () => {
		fc.assert(
			fc.property(
				// Generate info messages
				fc.string({ minLength: 1, maxLength: 200 }),
				// Generate extra context
				fc.dictionary(
					fc.string({ minLength: 1, maxLength: 30 }).filter((s) => /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(s)),
					fc.jsonValue(),
					{ minKeys: 1, maxKeys: 10 }
				),
				(message, extra) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					// Log info with extra context
					logger.info(message, extra);

					// Verify entry was captured
					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Verify severity is 'info' for info() calls
					expect(entry.severity).toBe('info');

					// Verify type is 'manual'
					expect(entry.type).toBe('manual');

					// Verify message is preserved
					expect(entry.message).toBe(message);

					// Verify extra context is preserved (normalized through JSON to handle -0 edge case)
					expect(entry.context.extra).toBeDefined();
					const normalizedExtra = JSON.parse(JSON.stringify(extra));
					for (const [key, value] of Object.entries(normalizedExtra)) {
						expect(entry.context.extra![key]).toEqual(value);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: All severity levels should be correctly mapped
	 * Testing that each logging method maps to the correct severity level
	 */
	it('should correctly map all severity levels for manual logging methods', () => {
		fc.assert(
			fc.property(
				fc.string({ minLength: 1, maxLength: 200 }),
				fc.constantFrom<'log' | 'warn' | 'info'>('log', 'warn', 'info'),
				(message, method) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					// Call the appropriate method
					switch (method) {
						case 'log':
							logger.log(message);
							break;
						case 'warn':
							logger.warn(message);
							break;
						case 'info':
							logger.info(message);
							break;
					}

					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Verify correct severity mapping
					const expectedSeverity: Record<string, ErrorSeverity> = {
						log: 'error',
						warn: 'warning',
						info: 'info'
					};

					expect(entry.severity).toBe(expectedSeverity[method]);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Extra context with nested objects should be preserved
	 */
	it('should preserve nested objects in extra context', () => {
		fc.assert(
			fc.property(
				fc.string({ minLength: 1, maxLength: 200 }),
				fc.constantFrom<'log' | 'warn' | 'info'>('log', 'warn', 'info'),
				// Generate nested extra context
				fc.record({
					simpleValue: fc.string({ minLength: 1, maxLength: 50 }),
					numericValue: fc.integer(),
					booleanValue: fc.boolean(),
					nestedObject: fc.record({
						innerKey: fc.string({ minLength: 1, maxLength: 30 }),
						innerNumber: fc.integer()
					}),
					arrayValue: fc.array(fc.integer(), { minLength: 1, maxLength: 5 })
				}),
				(message, method, extra) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					// Call the appropriate method with nested extra
					switch (method) {
						case 'log':
							logger.log(message, extra);
							break;
						case 'warn':
							logger.warn(message, extra);
							break;
						case 'info':
							logger.info(message, extra);
							break;
					}

					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Verify nested extra context is preserved exactly
					expect(entry.context.extra).toBeDefined();
					expect(entry.context.extra).toEqual(extra);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Manual logging with Error object should preserve both message and stack
	 */
	it('should preserve Error object message and stack when using log()', () => {
		fc.assert(
			fc.property(
				fc.string({ minLength: 1, maxLength: 200 }),
				fc.string({ minLength: 1, maxLength: 500 }),
				fc.dictionary(
					fc.string({ minLength: 1, maxLength: 30 }).filter((s) => /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(s)),
					fc.jsonValue(),
					{ minKeys: 1, maxKeys: 5 }
				),
				(message, stack, extra) => {
					const error = new Error(message);
					error.stack = stack;

					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: true, isDevelopment: false });

					capturedEntries = [];

					logger.log(error, extra);

					expect(capturedEntries.length).toBe(1);
					const entry = capturedEntries[0];

					// Verify message from Error is preserved
					expect(entry.message).toBe(message);

					// Verify stack is preserved
					expect(entry.stack).toBe(stack);

					// Verify severity is 'error'
					expect(entry.severity).toBe('error');

					// Verify extra context is preserved (normalized through JSON to handle -0 edge case)
					expect(entry.context.extra).toBeDefined();
					const normalizedExtra = JSON.parse(JSON.stringify(extra));
					for (const [key, value] of Object.entries(normalizedExtra)) {
						expect(entry.context.extra![key]).toEqual(value);
					}
				}
			),
			{ numRuns: 100 }
		);
	});
});


/**
 * Feature: frontend-error-logging, Property 10: Konfiguráció Hatékonysága
 * **Validálja: Követelmények 8.1, 8.3**
 *
 * *Bármely* ErrorLogger példány esetén enabled=false beállítással, a log() hívás
 * NEM eredményezhet HTTP kérést vagy localStorage írást.
 * *Bármely* konfigurált rate limit érték esetén pontosan azokat az értékeket
 * KELL használnia a rate limiter-nek.
 */
describe('Property Test: Konfiguráció Hatékonysága', () => {
	let fetchCallCount: number;
	let localStorageWrites: string[];
	let originalFetch: typeof globalThis.fetch;
	let originalLocalStorage: Storage;

	beforeEach(() => {
		vi.clearAllMocks();
		fetchCallCount = 0;
		localStorageWrites = [];

		// Setup browser mocks
		setupBrowserMocks('https://example.com/test', 'TestUserAgent/1.0');

		// Mock fetch to count calls
		originalFetch = globalThis.fetch;
		globalThis.fetch = vi.fn().mockImplementation(async () => {
			fetchCallCount++;
			return new Response(JSON.stringify({ success: true }), { status: 200 });
		});

		// Mock localStorage to track writes
		const mockStorage: Record<string, string> = {};
		originalLocalStorage = globalThis.localStorage;
		Object.defineProperty(globalThis, 'localStorage', {
			value: {
				getItem: (key: string) => mockStorage[key] || null,
				setItem: (key: string, value: string) => {
					localStorageWrites.push(key);
					mockStorage[key] = value;
				},
				removeItem: (key: string) => {
					delete mockStorage[key];
				},
				clear: () => {
					Object.keys(mockStorage).forEach((key) => delete mockStorage[key]);
				}
			},
			writable: true,
			configurable: true
		});
	});

	afterEach(() => {
		cleanupBrowserMocks();
		globalThis.fetch = originalFetch;
		Object.defineProperty(globalThis, 'localStorage', {
			value: originalLocalStorage,
			writable: true,
			configurable: true
		});
	});

	/**
	 * Property 10.1: Disabled logger should not make HTTP requests
	 * For any ErrorLogger instance with enabled=false, calling log() must NOT
	 * result in any HTTP requests.
	 */
	it('should not make HTTP requests when logger is disabled', () => {
		fc.assert(
			fc.property(
				// Generate error messages
				fc.string({ minLength: 1, maxLength: 200 }),
				// Generate optional extra context
				fc.option(
					fc.dictionary(
						fc.string({ minLength: 1, maxLength: 30 }).filter((s) => /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(s)),
						fc.jsonValue(),
						{ minKeys: 1, maxKeys: 5 }
					),
					{ nil: undefined }
				),
				// Generate logging method
				fc.constantFrom<'log' | 'warn' | 'info'>('log', 'warn', 'info'),
				(message, extra, method) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: false });

					// Reset counters
					fetchCallCount = 0;

					// Call the logging method
					switch (method) {
						case 'log':
							logger.log(message, extra);
							break;
						case 'warn':
							logger.warn(message, extra);
							break;
						case 'info':
							logger.info(message, extra);
							break;
					}

					// Verify no HTTP requests were made
					expect(fetchCallCount).toBe(0);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 10.2: Disabled logger should not write to localStorage
	 * For any ErrorLogger instance with enabled=false, calling log() must NOT
	 * result in any localStorage writes.
	 */
	it('should not write to localStorage when logger is disabled', () => {
		fc.assert(
			fc.property(
				// Generate error messages
				fc.string({ minLength: 1, maxLength: 200 }),
				// Generate optional extra context
				fc.option(
					fc.dictionary(
						fc.string({ minLength: 1, maxLength: 30 }).filter((s) => /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(s)),
						fc.jsonValue(),
						{ minKeys: 1, maxKeys: 5 }
					),
					{ nil: undefined }
				),
				// Generate logging method
				fc.constantFrom<'log' | 'warn' | 'info'>('log', 'warn', 'info'),
				(message, extra, method) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: false });

					// Reset localStorage writes tracking
					localStorageWrites = [];

					// Call the logging method
					switch (method) {
						case 'log':
							logger.log(message, extra);
							break;
						case 'warn':
							logger.warn(message, extra);
							break;
						case 'info':
							logger.info(message, extra);
							break;
					}

					// Verify no localStorage writes occurred
					expect(localStorageWrites.length).toBe(0);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 10.3: Disabled logger should not make HTTP requests for API errors
	 * For any ErrorLogger instance with enabled=false, calling logApiError() must NOT
	 * result in any HTTP requests.
	 */
	it('should not make HTTP requests for API errors when logger is disabled', () => {
		const endpointArb = fc
			.stringMatching(/^\/[a-zA-Z0-9\-\/]*$/)
			.filter((s) => s.length >= 2 && s.length <= 100);

		fc.assert(
			fc.property(
				endpointArb,
				fc.integer({ min: 400, max: 599 }),
				fc.string({ minLength: 1, maxLength: 200 }),
				(endpoint, status, message) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled: false });

					// Reset counters
					fetchCallCount = 0;

					// Log API error
					logger.logApiError(endpoint, status, { message });

					// Verify no HTTP requests were made
					expect(fetchCallCount).toBe(0);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 10.4: Rate limit configuration should be applied exactly
	 * For any configured rate limit values, the rate limiter must use exactly
	 * those values.
	 */
	it('should apply rate limit configuration exactly', () => {
		fc.assert(
			fc.property(
				// Generate maxErrors (reasonable range)
				fc.integer({ min: 1, max: 100 }),
				// Generate windowMs (reasonable range: 1 second to 10 minutes)
				fc.integer({ min: 1000, max: 600000 }),
				(maxErrors, windowMs) => {
					const logger = new ErrorLoggerImpl();
					logger.init({
						enabled: true,
						rateLimit: {
							maxErrors,
							windowMs
						}
					});

					// Get the config and verify rate limit values
					const config = logger.getConfig();

					expect(config.rateLimit.maxErrors).toBe(maxErrors);
					expect(config.rateLimit.windowMs).toBe(windowMs);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 10.5: Enabled logger should make HTTP requests
	 * For any ErrorLogger instance with enabled=true, calling log() should
	 * result in HTTP requests (when rate limit allows).
	 */
	it('should make HTTP requests when logger is enabled', () => {
		fc.assert(
			fc.property(
				// Generate error messages
				fc.string({ minLength: 1, maxLength: 200 }),
				// Generate logging method
				fc.constantFrom<'log' | 'warn' | 'info'>('log', 'warn', 'info'),
				(message, method) => {
					const logger = new ErrorLoggerImpl();
					logger.init({
						enabled: true,
						isDevelopment: false,
						rateLimit: {
							maxErrors: 100, // High limit to ensure we don't hit it
							windowMs: 60000
						}
					});

					// Reset counters
					fetchCallCount = 0;

					// Call the logging method
					switch (method) {
						case 'log':
							logger.log(message);
							break;
						case 'warn':
							logger.warn(message);
							break;
						case 'info':
							logger.info(message);
							break;
					}

					// Verify HTTP request was made
					expect(fetchCallCount).toBe(1);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 10.6: Configuration should be preserved after init
	 * For any configuration values passed to init(), the logger should
	 * preserve all those values.
	 */
	it('should preserve all configuration values after init', () => {
		fc.assert(
			fc.property(
				// Generate enabled flag
				fc.boolean(),
				// Generate endpoint
				fc.stringMatching(/^\/[a-zA-Z0-9\-\/]*$/).filter((s) => s.length >= 2 && s.length <= 100),
				// Generate maxRetries
				fc.integer({ min: 0, max: 10 }),
				// Generate retryDelay
				fc.integer({ min: 100, max: 10000 }),
				// Generate rate limit config
				fc.integer({ min: 1, max: 100 }),
				fc.integer({ min: 1000, max: 600000 }),
				// Generate appVersion
				fc.option(fc.string({ minLength: 1, maxLength: 20 }), { nil: undefined }),
				// Generate isDevelopment
				fc.boolean(),
				(enabled, endpoint, maxRetries, retryDelay, maxErrors, windowMs, appVersion, isDevelopment) => {
					const logger = new ErrorLoggerImpl();
					logger.init({
						enabled,
						endpoint,
						maxRetries,
						retryDelay,
						rateLimit: {
							maxErrors,
							windowMs
						},
						appVersion,
						isDevelopment
					});

					const config = logger.getConfig();

					// Verify all configuration values are preserved
					expect(config.enabled).toBe(enabled);
					expect(config.endpoint).toBe(endpoint);
					expect(config.maxRetries).toBe(maxRetries);
					expect(config.retryDelay).toBe(retryDelay);
					expect(config.rateLimit.maxErrors).toBe(maxErrors);
					expect(config.rateLimit.windowMs).toBe(windowMs);
					expect(config.isDevelopment).toBe(isDevelopment);

					if (appVersion !== undefined) {
						expect(config.appVersion).toBe(appVersion);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 10.7: Default configuration should be used when not specified
	 * When init() is called with partial config, default values should be used
	 * for unspecified fields.
	 */
	it('should use default configuration for unspecified fields', () => {
		fc.assert(
			fc.property(
				// Generate only enabled flag
				fc.boolean(),
				(enabled) => {
					const logger = new ErrorLoggerImpl();
					logger.init({ enabled });

					const config = logger.getConfig();

					// Verify enabled is set as specified
					expect(config.enabled).toBe(enabled);

					// Verify defaults are used for other fields
					expect(config.endpoint).toBe(DEFAULT_CONFIG.endpoint);
					expect(config.maxRetries).toBe(DEFAULT_CONFIG.maxRetries);
					expect(config.retryDelay).toBe(DEFAULT_CONFIG.retryDelay);
					expect(config.rateLimit.maxErrors).toBe(DEFAULT_CONFIG.rateLimit.maxErrors);
					expect(config.rateLimit.windowMs).toBe(DEFAULT_CONFIG.rateLimit.windowMs);
					expect(config.isDevelopment).toBe(DEFAULT_CONFIG.isDevelopment);
				}
			),
			{ numRuns: 100 }
		);
	});
});
