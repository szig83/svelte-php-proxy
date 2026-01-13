/**
 * Property Test: Rate Limiter Helyessége
 * **Property 4: Rate Limiter Helyessége**
 * **Validálja: Követelmények 4.3**
 *
 * *Bármely* N darab hibaküldés sorozat esetén T időablakon belül, ahol N meghaladja
 * a konfigurált maxErrors limitet, a rate limiter-nek blokkolnia KELL a további
 * küldéseket, amíg az időablak le nem jár.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import * as fc from 'fast-check';
import { SlidingWindowRateLimiter } from './rate-limiter';

describe('Property Test: Rate Limiter Helyessége', () => {
	beforeEach(() => {
		vi.useFakeTimers();
	});

	afterEach(() => {
		vi.useRealTimers();
	});

	/**
	 * Property 4: Rate Limiter Helyessége
	 * For any N sends within time window T, where N exceeds maxErrors,
	 * the rate limiter must block further sends until the window expires.
	 */
	it('should block sends when maxErrors limit is exceeded within the time window', () => {
		fc.assert(
			fc.property(
				// maxErrors: 1-20 (reasonable range for testing)
				fc.integer({ min: 1, max: 20 }),
				// windowMs: 1000-60000 (1 second to 1 minute)
				fc.integer({ min: 1000, max: 60000 }),
				// extraSends: how many sends beyond the limit to attempt (1-10)
				fc.integer({ min: 1, max: 10 }),
				(maxErrors, windowMs, extraSends) => {
					const limiter = new SlidingWindowRateLimiter(maxErrors, windowMs);

					// Record exactly maxErrors sends - all should be allowed
					for (let i = 0; i < maxErrors; i++) {
						expect(limiter.canSend()).toBe(true);
						limiter.recordSend();
					}

					// After maxErrors sends, further sends should be blocked
					for (let i = 0; i < extraSends; i++) {
						expect(limiter.canSend()).toBe(false);
					}
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: After time window expires, sends should be allowed again
	 */
	it('should allow sends again after the time window expires', () => {
		fc.assert(
			fc.property(
				fc.integer({ min: 1, max: 20 }),
				fc.integer({ min: 1000, max: 60000 }),
				(maxErrors, windowMs) => {
					const limiter = new SlidingWindowRateLimiter(maxErrors, windowMs);

					// Fill up the limit
					for (let i = 0; i < maxErrors; i++) {
						limiter.recordSend();
					}

					// Should be blocked now
					expect(limiter.canSend()).toBe(false);

					// Advance time past the window
					vi.advanceTimersByTime(windowMs + 1);

					// Should be allowed again
					expect(limiter.canSend()).toBe(true);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Sliding window behavior - old timestamps should be cleaned up
	 */
	it('should clean up old timestamps and allow new sends as window slides', () => {
		fc.assert(
			fc.property(
				fc.integer({ min: 2, max: 10 }),
				fc.integer({ min: 1000, max: 10000 }),
				(maxErrors, windowMs) => {
					const limiter = new SlidingWindowRateLimiter(maxErrors, windowMs);

					// Record one send
					limiter.recordSend();

					// Advance time to just before window expires
					vi.advanceTimersByTime(windowMs - 100);

					// Fill up remaining slots
					for (let i = 1; i < maxErrors; i++) {
						expect(limiter.canSend()).toBe(true);
						limiter.recordSend();
					}

					// Should be blocked now
					expect(limiter.canSend()).toBe(false);

					// Advance time so the first send expires (100ms + 1ms)
					vi.advanceTimersByTime(101);

					// Now one slot should be free
					expect(limiter.canSend()).toBe(true);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Reset should clear all timestamps and allow sends
	 */
	it('should allow sends after reset regardless of previous state', () => {
		fc.assert(
			fc.property(
				fc.integer({ min: 1, max: 20 }),
				fc.integer({ min: 1000, max: 60000 }),
				fc.integer({ min: 1, max: 50 }),
				(maxErrors, windowMs, sendCount) => {
					const limiter = new SlidingWindowRateLimiter(maxErrors, windowMs);

					// Record arbitrary number of sends
					for (let i = 0; i < sendCount; i++) {
						if (limiter.canSend()) {
							limiter.recordSend();
						}
					}

					// Reset the limiter
					limiter.reset();

					// Should be able to send again
					expect(limiter.canSend()).toBe(true);
					expect(limiter.getCurrentCount()).toBe(0);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Configuration values should be preserved
	 */
	it('should preserve configured maxErrors and windowMs values', () => {
		fc.assert(
			fc.property(
				fc.integer({ min: 1, max: 1000 }),
				fc.integer({ min: 100, max: 3600000 }),
				(maxErrors, windowMs) => {
					const limiter = new SlidingWindowRateLimiter(maxErrors, windowMs);

					expect(limiter.getMaxErrors()).toBe(maxErrors);
					expect(limiter.getWindowMs()).toBe(windowMs);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: getCurrentCount should accurately reflect sends within window
	 */
	it('should accurately track current count within the time window', () => {
		fc.assert(
			fc.property(
				fc.integer({ min: 5, max: 20 }),
				fc.integer({ min: 1000, max: 10000 }),
				fc.integer({ min: 1, max: 5 }),
				(maxErrors, windowMs, sendCount) => {
					const limiter = new SlidingWindowRateLimiter(maxErrors, windowMs);
					const actualSends = Math.min(sendCount, maxErrors);

					// Record sends
					for (let i = 0; i < actualSends; i++) {
						limiter.recordSend();
					}

					// Count should match
					expect(limiter.getCurrentCount()).toBe(actualSends);
				}
			),
			{ numRuns: 100 }
		);
	});
});
