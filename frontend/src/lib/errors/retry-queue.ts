/**
 * Retry Queue - Újrapróbálkozási Sor
 * A sikertelen küldéseket localStorage-ban tárolja és később újrapróbálja.
 * Követelmények: 4.2, 4.4
 */

import type { ErrorEntry, QueuedError, RetryQueue } from './types';

const STORAGE_KEY = 'error_retry_queue';
const MAX_QUEUE_SIZE = 50;

/**
 * LocalStorage alapú Retry Queue implementáció
 */
export class LocalStorageRetryQueue implements RetryQueue {
	private readonly storageKey: string;
	private readonly maxSize: number;

	/**
	 * @param storageKey LocalStorage kulcs (alapértelmezett: 'error_retry_queue')
	 * @param maxSize Maximum queue méret (alapértelmezett: 50)
	 */
	constructor(storageKey: string = STORAGE_KEY, maxSize: number = MAX_QUEUE_SIZE) {
		this.storageKey = storageKey;
		this.maxSize = maxSize;
	}

	/**
	 * Hiba hozzáadása a queue-hoz
	 * Ha a queue megtelt, a legrégebbi elemek törlődnek
	 * @param entry A hiba bejegyzés
	 */
	add(entry: ErrorEntry): void {
		const queue = this.getAll();

		const queuedError: QueuedError = {
			entry,
			attempts: 0,
			lastAttempt: Date.now()
		};

		queue.push(queuedError);

		// Ha túl sok elem van, töröljük a legrégebbieket (FIFO)
		while (queue.length > this.maxSize) {
			queue.shift();
		}

		this.save(queue);
	}

	/**
	 * Összes elem lekérése a queue-ból
	 * @returns A queue-ban lévő hibák listája
	 */
	getAll(): QueuedError[] {
		if (typeof window === 'undefined' || !window.localStorage) {
			return [];
		}

		try {
			const data = localStorage.getItem(this.storageKey);
			if (!data) {
				return [];
			}
			return JSON.parse(data) as QueuedError[];
		} catch {
			// Ha a parse sikertelen, üres tömböt adunk vissza
			return [];
		}
	}

	/**
	 * Elem törlése a queue-ból ID alapján
	 * @param id A törlendő hiba azonosítója
	 */
	remove(id: string): void {
		const queue = this.getAll();
		const filtered = queue.filter((item) => item.entry.id !== id);
		this.save(filtered);
	}

	/**
	 * Queue ürítése
	 */
	clear(): void {
		if (typeof window === 'undefined' || !window.localStorage) {
			return;
		}

		try {
			localStorage.removeItem(this.storageKey);
		} catch {
			// Hiba esetén nem csinálunk semmit
		}
	}

	/**
	 * Próbálkozás számláló növelése
	 * @param id A hiba azonosítója
	 */
	incrementAttempt(id: string): void {
		const queue = this.getAll();
		const item = queue.find((item) => item.entry.id === id);

		if (item) {
			item.attempts += 1;
			item.lastAttempt = Date.now();
			this.save(queue);
		}
	}

	/**
	 * Egy elem lekérése ID alapján
	 * @param id A hiba azonosítója
	 * @returns A queued error vagy undefined
	 */
	get(id: string): QueuedError | undefined {
		const queue = this.getAll();
		return queue.find((item) => item.entry.id === id);
	}

	/**
	 * Queue méretének lekérése
	 * @returns A queue-ban lévő elemek száma
	 */
	size(): number {
		return this.getAll().length;
	}

	/**
	 * Ellenőrzi, hogy a queue üres-e
	 * @returns true ha a queue üres
	 */
	isEmpty(): boolean {
		return this.size() === 0;
	}

	/**
	 * Queue mentése localStorage-ba
	 * @param queue A mentendő queue
	 */
	private save(queue: QueuedError[]): void {
		if (typeof window === 'undefined' || !window.localStorage) {
			return;
		}

		try {
			localStorage.setItem(this.storageKey, JSON.stringify(queue));
		} catch (e) {
			// Ha a localStorage megtelt, próbáljuk meg törölni a régi elemeket
			if (e instanceof DOMException && e.name === 'QuotaExceededError') {
				// Töröljük a queue felét és próbáljuk újra
				const reducedQueue = queue.slice(Math.floor(queue.length / 2));
				try {
					localStorage.setItem(this.storageKey, JSON.stringify(reducedQueue));
				} catch {
					// Ha még mindig nem fér el, töröljük az egészet
					localStorage.removeItem(this.storageKey);
				}
			}
		}
	}
}

/**
 * Alapértelmezett retry queue létrehozása
 * @param storageKey LocalStorage kulcs
 * @param maxSize Maximum queue méret
 */
export function createRetryQueue(
	storageKey: string = STORAGE_KEY,
	maxSize: number = MAX_QUEUE_SIZE
): RetryQueue {
	return new LocalStorageRetryQueue(storageKey, maxSize);
}

export { STORAGE_KEY, MAX_QUEUE_SIZE };
