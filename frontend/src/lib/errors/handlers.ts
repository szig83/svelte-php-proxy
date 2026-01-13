/**
 * Global Error Handlers
 * Globális hibakezelők a kezeletlen JavaScript hibák és Promise rejection-ök elkapásához.
 * Követelmények: 1.1, 1.2, 1.4
 */

import type { ErrorLogger } from './types';

/**
 * Globális hibakezelők beállítása
 * @param logger - Az ErrorLogger instance, amelyet a hibák naplózásához használunk
 *
 * Követelmények:
 * - 1.1: WHEN egy kezeletlen JavaScript hiba keletkezik, THE Error_Logger SHALL elkapja és naplózza a hibát
 * - 1.2: WHEN egy kezeletlen Promise rejection történik, THE Error_Logger SHALL elkapja és naplózza a hibát
 * - 1.4: WHEN egy hiba naplózásra kerül, THE Error_Logger SHALL nem szakítja meg az alkalmazás működését
 */
export function setupGlobalErrorHandlers(logger: ErrorLogger): void {
	if (typeof window === 'undefined') return;

	// JavaScript hibák elkapása (Követelmény 1.1)
	window.onerror = (
		message: string | Event,
		source?: string,
		lineno?: number,
		colno?: number,
		error?: Error
	): boolean => {
		logger.logJsError(error || new Error(String(message)), {
			source,
			lineno,
			colno,
			errorType: 'uncaughtError'
		});
		// Return false to not prevent the default error handling (Követelmény 1.4)
		return false;
	};

	// Kezeletlen Promise rejection elkapása (Követelmény 1.2)
	window.onunhandledrejection = (event: PromiseRejectionEvent): void => {
		const error =
			event.reason instanceof Error ? event.reason : new Error(String(event.reason));
		logger.logJsError(error, { errorType: 'unhandledrejection' });
		// Don't prevent default - let the browser also handle it (Követelmény 1.4)
	};
}

/**
 * Globális hibakezelők eltávolítása
 * Hasznos teszteléshez és cleanup-hoz
 */
export function removeGlobalErrorHandlers(): void {
	if (typeof window === 'undefined') return;

	window.onerror = null;
	window.onunhandledrejection = null;
}
