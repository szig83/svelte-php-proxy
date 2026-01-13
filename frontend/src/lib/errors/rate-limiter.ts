/**
 * Rate Limiter - Sebességkorlátozó
 * Megakadályozza, hogy túl sok hiba kerüljön elküldésre rövid időn belül.
 * Követelmény: 4.3
 */

import type { RateLimiter } from './types';

/**
 * Sliding Window Rate Limiter implementáció
 * Egy csúszó időablakban korlátozza a küldések számát.
 */
export class SlidingWindowRateLimiter implements RateLimiter {
	private timestamps: number[] = [];
	private readonly maxErrors: number;
	private readonly windowMs: number;

	/**
	 * @param maxErrors Maximum hibák száma az időablakban
	 * @param windowMs Időablak hossza milliszekundumban
	 */
	constructor(maxErrors: number, windowMs: number) {
		this.maxErrors = maxErrors;
		this.windowMs = windowMs;
	}

	/**
	 * Ellenőrzi, hogy küldhető-e újabb hiba
	 * @returns true ha a limit még nem lett elérve
	 */
	canSend(): boolean {
		this.cleanup();
		return this.timestamps.length < this.maxErrors;
	}

	/**
	 * Rögzíti egy küldés időpontját
	 */
	recordSend(): void {
		this.timestamps.push(Date.now());
	}

	/**
	 * Nullázza a számlálót
	 */
	reset(): void {
		this.timestamps = [];
	}

	/**
	 * Eltávolítja az időablakon kívüli időbélyegeket
	 */
	private cleanup(): void {
		const cutoff = Date.now() - this.windowMs;
		this.timestamps = this.timestamps.filter((t) => t > cutoff);
	}

	/**
	 * Visszaadja az aktuális küldések számát az időablakban
	 * (Teszteléshez hasznos)
	 */
	getCurrentCount(): number {
		this.cleanup();
		return this.timestamps.length;
	}

	/**
	 * Visszaadja a konfigurált maximum értéket
	 */
	getMaxErrors(): number {
		return this.maxErrors;
	}

	/**
	 * Visszaadja a konfigurált időablak hosszát
	 */
	getWindowMs(): number {
		return this.windowMs;
	}
}

/**
 * Alapértelmezett rate limiter létrehozása
 * @param maxErrors Maximum hibák száma (alapértelmezett: 10)
 * @param windowMs Időablak hossza ms-ban (alapértelmezett: 60000 = 1 perc)
 */
export function createRateLimiter(maxErrors = 10, windowMs = 60000): RateLimiter {
	return new SlidingWindowRateLimiter(maxErrors, windowMs);
}
