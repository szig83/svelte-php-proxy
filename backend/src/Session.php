<?php
/**
 * Session kezelő osztály
 *
 * Biztonságos session konfiguráció és kezelés.
 * Követelmények: 8.1, 8.2
 */

declare(strict_types=1);

namespace App;

class Session
{
    private static bool $started = false;
    private static bool $expired = false;

    /**
     * Session indítása biztonságos konfigurációval
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        // Biztonságos session konfiguráció
        $lifetime = \Config::getSessionLifetime();
        $sessionName = \Config::getSessionName();
        $isSecure = self::isSecureConnection();

        // Session cookie beállítások
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Session név beállítása
        session_name($sessionName);

        // Session indítása
        session_start();

        // Session regenerálás ha szükséges (session fixation védelem)
        if (!isset($_SESSION['_initialized'])) {
            session_regenerate_id(true);
            $_SESSION['_initialized'] = true;
            $_SESSION['_created_at'] = time();
        }

        // Session lejárat ellenőrzése
        self::checkExpiration($lifetime);

        self::$started = true;
    }

    /**
     * Session lejárat ellenőrzése
     * Követelmények: 8.2
     *
     * @return bool True ha a session lejárt
     */
    private static function checkExpiration(int $lifetime): bool
    {
        if (isset($_SESSION['_last_activity'])) {
            $elapsed = time() - $_SESSION['_last_activity'];
            if ($elapsed > $lifetime) {
                self::$expired = true;
                self::destroy();
                return true;
            }
        }
        $_SESSION['_last_activity'] = time();
        return false;
    }

    /**
     * Ellenőrzi, hogy a session lejárt-e
     * Követelmények: 8.2
     *
     * @return bool True ha a session lejárt
     */
    public static function isExpired(): bool
    {
        return self::$expired;
    }

    /**
     * Ellenőrzi, hogy a session érvényes-e (aktív és nem lejárt)
     * Követelmények: 8.2
     *
     * @return bool True ha a session érvényes
     */
    public static function isValid(): bool
    {
        self::ensureStarted();

        // Ha a session lejárt, nem érvényes
        if (self::$expired) {
            return false;
        }

        // Ha nincs access token, nem érvényes autentikált session
        if (!isset($_SESSION['access_token'])) {
            return false;
        }

        return true;
    }

    /**
     * Érték beállítása a session-ben
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Érték lekérése a session-ből
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Érték létezésének ellenőrzése
     */
    public static function has(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Érték törlése a session-ből
     */
    public static function remove(string $key): void
    {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Session megsemmisítése
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Session adatok törlése
            $_SESSION = [];

            // Session cookie törlése
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            // Session megsemmisítése
            session_destroy();
        }

        self::$started = false;
    }

    /**
     * Session ID regenerálása (biztonsági okokból)
     */
    public static function regenerate(): void
    {
        self::ensureStarted();
        session_regenerate_id(true);
    }

    /**
     * Ellenőrzi, hogy a session aktív-e
     */
    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Biztosítja, hogy a session el legyen indítva
     */
    private static function ensureStarted(): void
    {
        if (!self::$started && session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
    }

    /**
     * Ellenőrzi, hogy HTTPS kapcsolaton vagyunk-e
     */
    private static function isSecureConnection(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
}
