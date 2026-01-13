<?php
/**
 * Bootstrap fájl - .env betöltés és Config osztály
 *
 * Ez a fájl NEM tárol titkokat - csak betölti a .env-ből
 * és kényelmes hozzáférést biztosít hozzájuk.
 */

declare(strict_types=1);

// Composer autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

// .env betöltése
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Kötelező változók ellenőrzése
$dotenv->required([
    'EXTERNAL_API_URL',
    'ENCRYPTION_KEY',
    'SYSTEM_ID'
])->notEmpty();

/**
 * Konfiguráció osztály - statikus metódusokkal biztosít hozzáférést
 * a környezeti változókhoz
 */
class Config
{
    /**
     * Általános getter környezeti változókhoz
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }

    /**
     * Külső API URL
     */
    public static function getExternalApiUrl(): string
    {
        return $_ENV['EXTERNAL_API_URL'];
    }

    /**
     * Külső API timeout másodpercben
     */
    public static function getExternalApiTimeout(): int
    {
        return (int) ($_ENV['EXTERNAL_API_TIMEOUT'] ?? 30);
    }

    /**
     * Titkosítási kulcs
     */
    public static function getEncryptionKey(): string
    {
        return $_ENV['ENCRYPTION_KEY'];
    }

    /**
     * Rendszer azonosító
     */
    public static function getSystemId(): string
    {
        return $_ENV['SYSTEM_ID'];
    }

    /**
     * Session élettartam másodpercben
     */
    public static function getSessionLifetime(): int
    {
        return (int) ($_ENV['SESSION_LIFETIME'] ?? 3600);
    }

    /**
     * Session név
     */
    public static function getSessionName(): string
    {
        return $_ENV['SESSION_NAME'] ?? 'myapp_session';
    }

    /**
     * Debug mód aktív-e
     */
    public static function isDebugMode(): bool
    {
        return filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Rate limit - kérések száma
     */
    public static function getRateLimitRequests(): int
    {
        return (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 100);
    }

    /**
     * Rate limit - időablak másodpercben
     */
    public static function getRateLimitWindow(): int
    {
        return (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60);
    }

    /**
     * SSL tanúsítvány ellenőrzés
     * Fejlesztéshez kikapcsolható, production-ben MINDIG true legyen!
     */
    public static function getSslVerify(): bool
    {
        return filter_var($_ENV['SSL_VERIFY'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }
}
