/**
 * Error Logger Types and Interfaces
 * Frontend hibanaplózási rendszer típusdefiníciói
 */

/**
 * Hiba típusok
 */
export type ErrorType = 'javascript' | 'api' | 'manual' | 'php';

/**
 * Súlyossági szintek
 */
export type ErrorSeverity = 'error' | 'warning' | 'info';

/**
 * Hiba kontextus információk
 * Követelmények: 3.1, 3.2, 3.3, 3.4, 3.5
 */
export interface ErrorContext {
	/** Aktuális oldal URL (Követelmény 3.1) */
	url: string;
	/** Böngésző user agent (Követelmény 3.2) */
	userAgent: string;
	/** Bejelentkezett felhasználó azonosító (Követelmény 3.4) */
	userId?: string;
	/** Alkalmazás verzió (Követelmény 3.5) */
	appVersion?: string;
	/** Egyedi kontextus adatok */
	extra?: Record<string, unknown>;
}

/**
 * Hiba bejegyzés
 * Egy naplózott hiba rekord, amely tartalmazza a hiba részleteit és kontextusát
 */
export interface ErrorEntry {
	/** Egyedi azonosító */
	id: string;
	/** Hiba típusa */
	type: ErrorType;
	/** Súlyosság */
	severity: ErrorSeverity;
	/** Hiba üzenet */
	message: string;
	/** Stack trace (ha elérhető) */
	stack?: string;
	/** Kontextus információk */
	context: ErrorContext;
	/** Időbélyeg (ISO 8601 formátum) (Követelmény 3.3) */
	timestamp: string;
}

/**
 * Rate limiter konfiguráció
 */
export interface RateLimitConfig {
	/** Maximum hibák száma az időablakban */
	maxErrors: number;
	/** Időablak hossza (ms) */
	windowMs: number;
}

/**
 * Error Logger konfiguráció
 */
export interface ErrorLoggerConfig {
	/** Naplózás engedélyezve */
	enabled: boolean;
	/** Backend API URL */
	endpoint: string;
	/** Maximum újrapróbálkozások száma */
	maxRetries: number;
	/** Újrapróbálkozás késleltetése (ms) */
	retryDelay: number;
	/** Rate limiting beállítások */
	rateLimit: RateLimitConfig;
	/** Alkalmazás verzió */
	appVersion?: string;
	/** Fejlesztői mód */
	isDevelopment: boolean;
}

/**
 * Queued error a retry queue-ban
 */
export interface QueuedError {
	/** A hiba bejegyzés */
	entry: ErrorEntry;
	/** Próbálkozások száma */
	attempts: number;
	/** Utolsó próbálkozás időpontja */
	lastAttempt: number;
}

/**
 * API hiba típus
 */
export interface ApiError {
	/** Hiba üzenet */
	message: string;
	/** Hiba kód */
	code?: string;
	/** További részletek */
	details?: Record<string, unknown>;
}

/**
 * Error Logger interfész
 */
export interface ErrorLogger {
	/** Inicializálás */
	init(config: Partial<ErrorLoggerConfig>): void;

	/** Manuális hiba naplózás */
	log(error: Error | string, extra?: Record<string, unknown>): void;

	/** Figyelmeztetés naplózás */
	warn(message: string, extra?: Record<string, unknown>): void;

	/** Info naplózás */
	info(message: string, extra?: Record<string, unknown>): void;

	/** API hiba naplózás */
	logApiError(endpoint: string, status: number, error: ApiError): void;

	/** JavaScript hiba naplózás (globális hibakezelőkhöz) */
	logJsError(error: Error | string, extra?: Record<string, unknown>): void;

	/** Globális handler-ek regisztrálása */
	registerGlobalHandlers(): void;

	/** Retry queue feldolgozása */
	processRetryQueue(): Promise<void>;
}

/**
 * Rate Limiter interfész
 */
export interface RateLimiter {
	/** Küldhető-e hiba */
	canSend(): boolean;
	/** Küldés rögzítése */
	recordSend(): void;
	/** Számláló nullázása */
	reset(): void;
}

/**
 * Retry Queue interfész
 */
export interface RetryQueue {
	/** Hiba hozzáadása */
	add(entry: ErrorEntry): void;
	/** Összes elem lekérése */
	getAll(): QueuedError[];
	/** Elem törlése */
	remove(id: string): void;
	/** Sor ürítése */
	clear(): void;
	/** Próbálkozás számláló növelése */
	incrementAttempt(id: string): void;
}
