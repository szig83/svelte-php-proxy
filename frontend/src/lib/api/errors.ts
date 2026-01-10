// src/lib/api/errors.ts
// Hibakezelés implementálása
// Követelmények: 7.3, 7.4, 4.5

import { ErrorCode, type ApiError } from './client';

/**
 * User-friendly error messages for each error code
 */
const ERROR_MESSAGES: Record<string, string> = {
	[ErrorCode.NETWORK_ERROR]: 'Hálózati hiba történt. Kérjük, ellenőrizze az internetkapcsolatát.',
	[ErrorCode.UNAUTHORIZED]: 'A munkamenet lejárt. Kérjük, jelentkezzen be újra.',
	[ErrorCode.FORBIDDEN]: 'Nincs jogosultsága ehhez a művelethez.',
	[ErrorCode.NOT_FOUND]: 'A keresett erőforrás nem található.',
	[ErrorCode.VALIDATION_ERROR]: 'Érvénytelen adatok. Kérjük, ellenőrizze a megadott információkat.',
	[ErrorCode.SERVER_ERROR]: 'Szerverhiba történt. Kérjük, próbálja újra később.',
	[ErrorCode.CSRF_ERROR]: 'Biztonsági hiba. Kérjük, frissítse az oldalt és próbálja újra.',
	[ErrorCode.RATE_LIMITED]: 'Túl sok kérés. Kérjük, várjon egy kicsit és próbálja újra.'
};

/**
 * Get user-friendly error message
 * @param error - API error object
 * @returns User-friendly error message
 */
export function getErrorMessage(error: ApiError | undefined): string {
	if (!error) {
		return 'Ismeretlen hiba történt.';
	}

	// Use custom message if provided and not a generic code
	if (error.message && !Object.values(ErrorCode).includes(error.code as ErrorCode)) {
		return error.message;
	}

	// Use predefined message for known error codes
	return ERROR_MESSAGES[error.code] || error.message || 'Ismeretlen hiba történt.';
}

/**
 * Check if error is an authentication error
 * @param error - API error object
 */
export function isAuthError(error: ApiError | undefined): boolean {
	return error?.code === ErrorCode.UNAUTHORIZED;
}

/**
 * Check if error is a permission error
 * @param error - API error object
 */
export function isPermissionError(error: ApiError | undefined): boolean {
	return error?.code === ErrorCode.FORBIDDEN;
}

/**
 * Check if error is a validation error
 * @param error - API error object
 */
export function isValidationError(error: ApiError | undefined): boolean {
	return error?.code === ErrorCode.VALIDATION_ERROR;
}

/**
 * Check if error is a network error
 * @param error - API error object
 */
export function isNetworkError(error: ApiError | undefined): boolean {
	return error?.code === ErrorCode.NETWORK_ERROR;
}

/**
 * Check if error is a server error
 * @param error - API error object
 */
export function isServerError(error: ApiError | undefined): boolean {
	return error?.code === ErrorCode.SERVER_ERROR;
}

/**
 * Check if error is rate limiting
 * @param error - API error object
 */
export function isRateLimited(error: ApiError | undefined): boolean {
	return error?.code === ErrorCode.RATE_LIMITED;
}

/**
 * Get validation error details for a specific field
 * @param error - API error object
 * @param field - Field name
 * @returns Array of error messages for the field
 */
export function getFieldErrors(error: ApiError | undefined, field: string): string[] {
	if (!error?.details) {
		return [];
	}
	return error.details[field] || [];
}

/**
 * Check if a specific field has validation errors
 * @param error - API error object
 * @param field - Field name
 */
export function hasFieldError(error: ApiError | undefined, field: string): boolean {
	return getFieldErrors(error, field).length > 0;
}

/**
 * Error handler class for managing API errors in components
 */
export class ApiErrorHandler {
	private error: ApiError | null = null;

	/**
	 * Set the current error
	 */
	setError(error: ApiError | undefined): void {
		this.error = error || null;
	}

	/**
	 * Clear the current error
	 */
	clearError(): void {
		this.error = null;
	}

	/**
	 * Get the current error
	 */
	getError(): ApiError | null {
		return this.error;
	}

	/**
	 * Check if there's an error
	 */
	hasError(): boolean {
		return this.error !== null;
	}

	/**
	 * Get user-friendly message for current error
	 */
	getMessage(): string {
		return getErrorMessage(this.error || undefined);
	}

	/**
	 * Check if current error is auth error
	 */
	isAuthError(): boolean {
		return isAuthError(this.error || undefined);
	}

	/**
	 * Check if current error is permission error
	 */
	isPermissionError(): boolean {
		return isPermissionError(this.error || undefined);
	}

	/**
	 * Get field errors for current error
	 */
	getFieldErrors(field: string): string[] {
		return getFieldErrors(this.error || undefined, field);
	}
}
