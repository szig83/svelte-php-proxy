/**
 * Property Test: Retry Queue Perzisztencia
 * **Property 5: Retry Queue Perzisztencia**
 * **Validálja: Követelmények 4.2, 4.4**
 *
 * *Bármely* sikertelen hibaküldés esetén a hiba bejegyzésnek be KELL kerülnie
 * a retry queue-ba a localStorage-ban, és visszakereshetőnek KELL lennie
 * oldal újratöltés után is.
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import * as fc from 'fast-check';
import { LocalStorageRetryQueue } from './retry-queue';
import type { ErrorEntry, ErrorType, ErrorSeverity } from './types';

// Mock localStorage for testing
const createMockLocalStorage = () => {
	let store: Record<string, string> = {};
	return {
		getItem: (key: string) => store[key] ?? null,
		setItem: (key: string, value: string) => {
			store[key] = value;
		},
		removeItem: (key: string) => {
			delete store[key];
		},
		clear: () => {
			store = {};
		},
		get length() {
			return Object.keys(store).length;
		},
		key: (index: number) => Object.keys(store)[index] ?? null,
		getStore: () => store
	};
};

// Arbitrary for generating valid ErrorEntry objects
const errorTypeArb = fc.constantFrom<ErrorType>('javascript', 'api', 'manual');
const errorSeverityArb = fc.constantFrom<ErrorSeverity>('error', 'warning', 'info');

const errorContextArb = fc.record({
	url: fc.webUrl(),
	userAgent: fc.string({ minLength: 1, maxLength: 200 }),
	userId: fc.option(fc.string({ minLength: 1, maxLength: 50 }), { nil: undefined }),
	appVersion: fc.option(fc.string({ minLength: 1, maxLength: 20 }), { nil: undefined }),
	extra: fc.option(fc.dictionary(fc.string(), fc.jsonValue()), { nil: undefined })
});

// Use integer timestamps to avoid invalid date values
const validDateArb = fc
	.integer({ min: 1577836800000, max: 1924905600000 }) // 2020-01-01 to 2030-12-31
	.map((ts) => new Date(ts).toISOString());

const errorEntryArb: fc.Arbitrary<ErrorEntry> = fc.record({
	id: fc.uuid(),
	type: errorTypeArb,
	severity: errorSeverityArb,
	message: fc.string({ minLength: 1, maxLength: 500 }),
	stack: fc.option(fc.string({ minLength: 1, maxLength: 2000 }), { nil: undefined }),
	context: errorContextArb,
	timestamp: validDateArb
});

describe('Property Test: Retry Queue Perzisztencia', () => {
	let mockStorage: ReturnType<typeof createMockLocalStorage>;
	const TEST_STORAGE_KEY = 'test_error_retry_queue';

	beforeEach(() => {
		mockStorage = createMockLocalStorage();
		Object.defineProperty(global, 'window', {
			value: { localStorage: mockStorage },
			writable: true
		});
		Object.defineProperty(global, 'localStorage', {
			value: mockStorage,
			writable: true
		});
	});

	afterEach(() => {
		mockStorage.clear();
	});

	/**
	 * Property 5: Retry Queue Perzisztencia
	 * For any error entry added to the queue, it must be retrievable
	 * after simulating a page reload (new queue instance with same storage key).
	 */
	it('should persist error entries and retrieve them after page reload simulation', () => {
		fc.assert(
			fc.property(errorEntryArb, (entry) => {
				// Create first queue instance and add entry
				const queue1 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, 50);
				queue1.add(entry);

				// Simulate page reload by creating a new queue instance
				const queue2 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, 50);
				const retrieved = queue2.getAll();

				// Entry should be retrievable
				expect(retrieved.length).toBeGreaterThan(0);
				const found = retrieved.find((q) => q.entry.id === entry.id);
				expect(found).toBeDefined();
				expect(found?.entry.id).toBe(entry.id);
				expect(found?.entry.type).toBe(entry.type);
				expect(found?.entry.severity).toBe(entry.severity);
				expect(found?.entry.message).toBe(entry.message);
				expect(found?.entry.timestamp).toBe(entry.timestamp);

				// Cleanup for next iteration
				queue2.clear();
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Multiple entries should all be persisted and retrievable
	 */
	it('should persist multiple error entries and retrieve all after reload', () => {
		fc.assert(
			fc.property(
				fc.array(errorEntryArb, { minLength: 1, maxLength: 20 }),
				(entries) => {
					// Ensure unique IDs
					const uniqueEntries = entries.map((e, i) => ({ ...e, id: `${e.id}-${i}` }));

					// Create first queue instance and add all entries
					const queue1 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, 50);
					uniqueEntries.forEach((entry) => queue1.add(entry));

					// Simulate page reload
					const queue2 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, 50);
					const retrieved = queue2.getAll();

					// All entries should be retrievable (up to max size)
					const expectedCount = Math.min(uniqueEntries.length, 50);
					expect(retrieved.length).toBe(expectedCount);

					// Verify each entry is present (last N entries if over limit)
					const expectedEntries = uniqueEntries.slice(-expectedCount);
					expectedEntries.forEach((entry) => {
						const found = retrieved.find((q) => q.entry.id === entry.id);
						expect(found).toBeDefined();
					});

					// Cleanup
					queue2.clear();
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Removed entries should not be retrievable after reload
	 */
	it('should not retrieve removed entries after reload', () => {
		fc.assert(
			fc.property(errorEntryArb, (entry) => {
				// Add entry
				const queue1 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, 50);
				queue1.add(entry);

				// Remove entry
				queue1.remove(entry.id);

				// Simulate page reload
				const queue2 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, 50);
				const retrieved = queue2.getAll();

				// Entry should not be found
				const found = retrieved.find((q) => q.entry.id === entry.id);
				expect(found).toBeUndefined();

				// Cleanup
				queue2.clear();
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Queue should respect max size limit
	 */
	it('should respect max size limit and remove oldest entries', () => {
		fc.assert(
			fc.property(
				fc.integer({ min: 1, max: 10 }),
				fc.array(errorEntryArb, { minLength: 1, maxLength: 30 }),
				(maxSize, entries) => {
					// Ensure unique IDs
					const uniqueEntries = entries.map((e, i) => ({ ...e, id: `${e.id}-${i}` }));

					const queue = new LocalStorageRetryQueue(TEST_STORAGE_KEY, maxSize);
					uniqueEntries.forEach((entry) => queue.add(entry));

					// Simulate reload
					const queue2 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, maxSize);
					const retrieved = queue2.getAll();

					// Should not exceed max size
					expect(retrieved.length).toBeLessThanOrEqual(maxSize);

					// If we added more than maxSize, only the last maxSize entries should remain
					if (uniqueEntries.length > maxSize) {
						const expectedEntries = uniqueEntries.slice(-maxSize);
						expectedEntries.forEach((entry) => {
							const found = retrieved.find((q) => q.entry.id === entry.id);
							expect(found).toBeDefined();
						});
					}

					// Cleanup
					queue2.clear();
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Attempt counter should be persisted
	 */
	it('should persist attempt counter increments after reload', () => {
		fc.assert(
			fc.property(
				errorEntryArb,
				fc.integer({ min: 1, max: 10 }),
				(entry, incrementCount) => {
					// Add entry and increment attempts
					const queue1 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, 50);
					queue1.add(entry);

					for (let i = 0; i < incrementCount; i++) {
						queue1.incrementAttempt(entry.id);
					}

					// Simulate page reload
					const queue2 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, 50);
					const found = queue2.get(entry.id);

					// Attempt count should be persisted
					expect(found).toBeDefined();
					expect(found?.attempts).toBe(incrementCount);

					// Cleanup
					queue2.clear();
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property: Clear should remove all entries from storage
	 */
	it('should clear all entries from storage', () => {
		fc.assert(
			fc.property(
				fc.array(errorEntryArb, { minLength: 1, maxLength: 20 }),
				(entries) => {
					// Ensure unique IDs
					const uniqueEntries = entries.map((e, i) => ({ ...e, id: `${e.id}-${i}` }));

					// Add entries
					const queue1 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, 50);
					uniqueEntries.forEach((entry) => queue1.add(entry));

					// Clear
					queue1.clear();

					// Simulate reload
					const queue2 = new LocalStorageRetryQueue(TEST_STORAGE_KEY, 50);
					const retrieved = queue2.getAll();

					// Should be empty
					expect(retrieved.length).toBe(0);
				}
			),
			{ numRuns: 100 }
		);
	});
});
