<?php
/**
 * Rate Limiter osztály
 *
 * Kérés számláló session-ben, limit ellenőrzés auth végpontoknál.
 * Követelmények: 10.5
 */

declare(strict_types=1);

namespace App;

class RateLimiter
{
    private const REQUESTS_KEY = 'rate_limit_requests';
    private const WINDOW_START_KEY = 'rate_limit_window_start';

    /**
     * Rate limit ellenőrzése
     *
     * @return bool True ha a kérés engedélyezett, false ha túllépte a limitet
     */
    public static function check(): bool
    {
        $maxRequests = \Config::getRateLimitRequests();
        $windowSeconds = \Config::getRateLimitWindow();

        $currentTime = time();
        $windowStart = Session::get(self::WINDOW_START_KEY);
        $requestCount = Session::get(self::REQUESTS_KEY, 0);

        // Ha nincs még időablak, vagy lejárt, új ablakot kezdünk
        if ($windowStart === null || ($currentTime - $windowStart) >= $windowSeconds) {
            Session::set(self::WINDOW_START_KEY, $currentTime);
            Session::set(self::REQUESTS_KEY, 1);
            return true;
        }

        // Ellenőrizzük, hogy túlléptük-e a limitet
        if ($requestCount >= $maxRequests) {
            return false;
        }

        // Kérés számláló növelése
        Session::set(self::REQUESTS_KEY, $requestCount + 1);
        return true;
    }

    /**
     * Rate limit számláló nullázása
     * (pl. sikeres bejelentkezés után)
     */
    public static function reset(): void
    {
        Session::remove(self::REQUESTS_KEY);
        Session::remove(self::WINDOW_START_KEY);
    }

    /**
     * Hátralévő kérések száma
     *
     * @return int Hátralévő kérések száma
     */
    public static function getRemainingRequests(): int
    {
        $maxRequests = \Config::getRateLimitRequests();
        $requestCount = Session::get(self::REQUESTS_KEY, 0);

        return max(0, $maxRequests - $requestCount);
    }

    /**
     * Időablak lejáratáig hátralévő másodpercek
     *
     * @return int Hátralévő másodpercek
     */
    public static function getSecondsUntilReset(): int
    {
        $windowSeconds = \Config::getRateLimitWindow();
        $windowStart = Session::get(self::WINDOW_START_KEY);

        if ($windowStart === null) {
            return 0;
        }

        $elapsed = time() - $windowStart;
        return max(0, $windowSeconds - $elapsed);
    }

    /**
     * Rate limit információk lekérése
     *
     * @return array Rate limit információk
     */
    public static function getInfo(): array
    {
        return [
            'limit' => \Config::getRateLimitRequests(),
            'remaining' => self::getRemainingRequests(),
            'reset_in' => self::getSecondsUntilReset()
        ];
    }
}
