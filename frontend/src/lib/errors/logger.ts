/**
 * Error Logger Module
 * Központi modul a hibák naplózásához és továbbításához.
 * Követelmények: 7.1, 7.2, 7.3, 2.1, 2.2, 2.3
 */

import type {
	ErrorLogger,
	ErrorLoggerConfig,
	ErrorEntry,
	ErrorContext,
	ErrorSeverity,
	ApiError,
	RateLimiter,
	RetryQueue
} from './types';
import { SlidingWindowRateLimiter } from './rate-limiter';
import { LocalStorageRetryQueue } from './retry-queue';
import { getUser } from '../auth/store.svelte';

/**
 * Alapértelmezett konfiguráció
 */
const DEFAULT_CONFIG: ErrorLoggerConfig = {
	enabled: true,
	endpoint: '/api/errors',
	maxRetries: 3,
	retryDelay: 1000,
	rateLimit: {
		maxErrors: 10,
		windowMs: 60000
	},
	isDevelopment: false
};

/**
 * Egyedi azonosító generálása
 */
function generateId(): string {
	return `err_${Date.now()}_${Math.random().toString(36).substring(2, 11)}`;
}

/**
 * Kontextus információk összegyűjtése
 * Követelmények: 3.1, 3.2, 3.3, 3.4, 3.5
 */
function collectContext(config: ErrorLoggerConfig, extra?: Record<string, unknown>): ErrorContext {
	const context: ErrorContext = {
		url: typeof window !== 'undefined' ? window.location.href : '',
		userAgent: typeof navigator !== 'undefined' ? navigator.userAgent : ''
	};

	// Felhasználó azonosító hozzáadása (Követelmény 3.4)
	const user = getUser();
	if (user?.id) {
		context.userId = user.id;
	}

	// Alkalmazás verzió hozzáadása (Követelmény 3.5)
	if (config.appVersion) {
		context.appVersion = config.appVersion;
	}

	// Egyedi kontextus hozzáadása
	if (extra && Object.keys(extra).length > 0) {
		context.extra = extra;
	}

	return context;
}

/**
 * Error Logger implementáció
 */
class ErrorLoggerImpl implements ErrorLogger {
	private config: ErrorLoggerConfig = DEFAULT_CONFIG;
	private rateLimiter: RateLimiter;
	private retryQueue: RetryQueue;
	private initialized = false;

	constructor() {
		this.rateLimiter = new SlidingWindowRateLimiter(
			DEFAULT_CONFIG.rateLimit.maxErrors,
			DEFAULT_CONFIG.rateLimit.windowMs
		);
		this.retryQueue = new LocalStorageRetryQueue();
	}

	/**
	 * Logger inicializálása
	 * Követelmény: 8.1, 8.2, 8.3
	 */
	init(config: Partial<ErrorLoggerConfig>): void {
		this.config = { ...DEFAULT_CONFIG, ...config };

		// Rate limiter újra inicializálása a konfigurációval
		if (config.rateLimit) {
			this.rateLimiter = new SlidingWindowRateLimiter(
				this.config.rateLimit.maxErrors,
				this.config.rateLimit.windowMs
			);
		}

		this.initialized = true;

		// Retry queue feldolgozása inicializáláskor
		if (this.config.enabled) {
			this.processRetryQueue().catch(() => {
				// Hiba esetén nem csinálunk semmit
			});
		}
	}

	/**
	 * Manuális hiba naplózás
	 * Követelmény: 7.1, 7.2
	 */
	log(error: Error | string, extra?: Record<string, unknown>): void {
		this.logEntry(error, 'error', extra);
	}

	/**
	 * Figyelmeztetés naplózás
	 * Követelmény: 7.3
	 */
	warn(message: string, extra?: Record<string, unknown>): void {
		this.logEntry(message, 'warning', extra);
	}

	/**
	 * Info naplózás
	 * Követelmény: 7.3
	 */
	info(message: string, extra?: Record<string, unknown>): void {
		this.logEntry(message, 'info', extra);
	}

	/**
	 * API hiba naplózás
	 * Követelmények: 2.1, 2.2, 2.3
	 */
	logApiError(endpoint: string, status: number, error: ApiError): void {
		const entry = this.createEntry(
			error.message || 'API Error',
			'api',
			'error',
			undefined,
			{
				endpoint,
				status,
				errorCode: error.code,
				errorDetails: error.details
			}
		);

		this.sendEntry(entry);
	}

	/**
	 * JavaScript hiba naplózás (globális hibakezelőkhöz)
	 * Követelmények: 1.1, 1.2
	 */
	logJsError(error: Error | string, extra?: Record<string, unknown>): void {
		const message = error instanceof Error ? error.message : error;
		const stack = error instanceof Error ? error.stack : undefined;

		const entry = this.createEntry(message, 'javascript', 'error', stack, extra);
		this.sendEntry(entry);
	}

	/**
	 * Globális hibakezelők regisztrálása
	 * Követelmények: 1.1, 1.2, 1.4
	 */
	registerGlobalHandlers(): void {
		if (typeof window === 'undefined') return;

		// JavaScript hibák elkapása (Követelmény 1.1)
		window.onerror = (message, source, lineno, colno, error) => {
			this.log(error || new Error(String(message)), {
				source,
				lineno,
				colno,
				type: 'uncaughtError'
			});
			return false; // Ne szakítsa meg a default kezelést (Követelmény 1.4)
		};

		// Kezeletlen Promise rejection elkapása (Követelmény 1.2)
		window.onunhandledrejection = (event) => {
			const error =
				event.reason instanceof Error ? event.reason : new Error(String(event.reason));
			this.log(error, { type: 'unhandledrejection' });
		};
	}

	/**
	 * Retry queue feldolgozása
	 * Követelmény: 4.2
	 */
	async processRetryQueue(): Promise<void> {
		if (!this.config.enabled) return;

		const queuedErrors = this.retryQueue.getAll();

		for (const queuedError of queuedErrors) {
			if (queuedError.attempts >= this.config.maxRetries) {
				// Maximum próbálkozások elérve, töröljük
				this.retryQueue.remove(queuedError.entry.id);
				continue;
			}

			try {
				await this.sendToBackend(queuedError.entry);
				this.retryQueue.remove(queuedError.entry.id);
			} catch {
				this.retryQueue.incrementAttempt(queuedError.entry.id);
			}
		}
	}

	/**
	 * Konfiguráció lekérése (teszteléshez)
	 */
	getConfig(): ErrorLoggerConfig {
		return { ...this.config };
	}

	/**
	 * Inicializálás állapotának lekérése (teszteléshez)
	 */
	isInitialized(): boolean {
		return this.initialized;
	}

	/**
	 * Belső metódus: hiba bejegyzés létrehozása és küldése
	 */
	private logEntry(
		error: Error | string,
		severity: ErrorSeverity,
		extra?: Record<string, unknown>
	): void {
		const message = error instanceof Error ? error.message : error;
		const stack = error instanceof Error ? error.stack : undefined;

		const entry = this.createEntry(message, 'manual', severity, stack, extra);
		this.sendEntry(entry);
	}

	/**
	 * Belső metódus: ErrorEntry létrehozása
	 */
	private createEntry(
		message: string,
		type: 'javascript' | 'api' | 'manual',
		severity: ErrorSeverity,
		stack?: string,
		extra?: Record<string, unknown>
	): ErrorEntry {
		return {
			id: generateId(),
			type,
			severity,
			message,
			stack,
			context: collectContext(this.config, extra),
			timestamp: new Date().toISOString()
		};
	}

	/**
	 * Belső metódus: hiba bejegyzés küldése
	 */
	private sendEntry(entry: ErrorEntry): void {
		// Ha a naplózás ki van kapcsolva, nem csinálunk semmit (Követelmény 8.1)
		if (!this.config.enabled) {
			return;
		}

		// Fejlesztői módban konzolra is kiírjuk (Követelmény 8.4)
		if (this.config.isDevelopment) {
			this.logToConsole(entry);
		}

		// Rate limiting ellenőrzése (Követelmény 4.3)
		if (!this.rateLimiter.canSend()) {
			// Ha a rate limit elérve, a queue-ba tesszük
			this.retryQueue.add(entry);
			return;
		}

		// Küldés a backendre
		this.rateLimiter.recordSend();
		this.sendToBackend(entry).catch(() => {
			// Sikertelen küldés esetén a retry queue-ba tesszük (Követelmény 4.2)
			this.retryQueue.add(entry);
		});
	}

	/**
	 * Belső metódus: hiba küldése a backendre
	 */
	private async sendToBackend(entry: ErrorEntry): Promise<void> {
		const response = await fetch(this.config.endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify(entry)
		});

		if (!response.ok) {
			throw new Error(`Failed to send error: ${response.status}`);
		}
	}

	/**
	 * Belső metódus: konzolra írás fejlesztői módban
	 */
	private logToConsole(entry: ErrorEntry): void {
		const prefix = `[ErrorLogger][${entry.severity.toUpperCase()}]`;

		switch (entry.severity) {
			case 'error':
				console.error(prefix, entry.message, entry);
				break;
			case 'warning':
				console.warn(prefix, entry.message, entry);
				break;
			case 'info':
				console.info(prefix, entry.message, entry);
				break;
		}
	}
}

// Singleton instance
let loggerInstance: ErrorLoggerImpl | null = null;

/**
 * Error Logger singleton lekérése
 */
export function getErrorLogger(): ErrorLogger {
	if (!loggerInstance) {
		loggerInstance = new ErrorLoggerImpl();
	}
	return loggerInstance;
}

/**
 * Error Logger inicializálása
 * Követelmény: 8.1, 8.2, 8.3, 8.4
 */
export function initErrorLogger(config: Partial<ErrorLoggerConfig>): ErrorLogger {
	const logger = getErrorLogger();
	logger.init(config);
	return logger;
}

/**
 * Gyors hozzáférés a log metódushoz
 */
export function logError(error: Error | string, extra?: Record<string, unknown>): void {
	getErrorLogger().log(error, extra);
}

/**
 * Gyors hozzáférés a warn metódushoz
 */
export function logWarning(message: string, extra?: Record<string, unknown>): void {
	getErrorLogger().warn(message, extra);
}

/**
 * Gyors hozzáférés az info metódushoz
 */
export function logInfo(message: string, extra?: Record<string, unknown>): void {
	getErrorLogger().info(message, extra);
}

/**
 * Gyors hozzáférés az API hiba naplózáshoz
 */
export function logApiError(endpoint: string, status: number, error: ApiError): void {
	getErrorLogger().logApiError(endpoint, status, error);
}

/**
 * Gyors hozzáférés a JavaScript hiba naplózáshoz
 */
export function logJsError(error: Error | string, extra?: Record<string, unknown>): void {
	getErrorLogger().logJsError(error, extra);
}

// Export for testing
export { ErrorLoggerImpl, generateId, collectContext, DEFAULT_CONFIG };
