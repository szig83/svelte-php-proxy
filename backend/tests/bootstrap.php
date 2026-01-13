<?php
/**
 * PHPUnit bootstrap file
 */

declare(strict_types=1);

// Composer autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Mock Config class for testing
class Config
{
    private static array $values = [
        'EXTERNAL_API_URL' => 'https://api.example.com',
        'EXTERNAL_API_TIMEOUT' => 30,
        'ENCRYPTION_KEY' => 'test-encryption-key-32-chars-xx',
        'SYSTEM_ID' => 'test-system-id',
        'SESSION_LIFETIME' => 3600,
        'SESSION_NAME' => 'test_session',
        'RATE_LIMIT_REQUESTS' => 100,
        'RATE_LIMIT_WINDOW' => 60,
        'DEBUG_MODE' => false,
    ];

    public static function get(string $key, $default = null)
    {
        return self::$values[$key] ?? $default;
    }

    public static function getExternalApiUrl(): string
    {
        return self::$values['EXTERNAL_API_URL'];
    }

    public static function getExternalApiTimeout(): int
    {
        return (int) self::$values['EXTERNAL_API_TIMEOUT'];
    }

    public static function getEncryptionKey(): string
    {
        return self::$values['ENCRYPTION_KEY'];
    }

    public static function getSystemId(): string
    {
        return self::$values['SYSTEM_ID'];
    }

    public static function getSessionLifetime(): int
    {
        return (int) self::$values['SESSION_LIFETIME'];
    }

    public static function getSessionName(): string
    {
        return self::$values['SESSION_NAME'];
    }

    public static function isDebugMode(): bool
    {
        return (bool) self::$values['DEBUG_MODE'];
    }

    public static function getRateLimitRequests(): int
    {
        return (int) self::$values['RATE_LIMIT_REQUESTS'];
    }

    public static function getRateLimitWindow(): int
    {
        return (int) self::$values['RATE_LIMIT_WINDOW'];
    }
}

// Load source files
require_once dirname(__DIR__) . '/src/Session.php';
require_once dirname(__DIR__) . '/src/CsrfProtection.php';
require_once dirname(__DIR__) . '/src/TokenHandler.php';
require_once dirname(__DIR__) . '/src/TokenRefresher.php';
require_once dirname(__DIR__) . '/src/RequestForwarder.php';
require_once dirname(__DIR__) . '/src/Response.php';
require_once dirname(__DIR__) . '/src/ErrorLogger.php';
