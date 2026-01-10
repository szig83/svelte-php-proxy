<?php
/**
 * Token kezelő osztály
 *
 * JWT tokenek tárolása, visszaolvasása és törlése a session-ben.
 * Követelmények: 2.2, 3.2, 3.4
 */

declare(strict_types=1);

namespace App;

class TokenHandler
{
    private const ACCESS_TOKEN_KEY = 'access_token';
    private const REFRESH_TOKEN_KEY = 'refresh_token';
    private const TOKEN_EXPIRES_AT_KEY = 'token_expires_at';
    private const USER_KEY = 'user';

    /**
     * Access token lekérése a session-ből
     */
    public static function getAccessToken(): ?string
    {
        return Session::get(self::ACCESS_TOKEN_KEY);
    }

    /**
     * Refresh token lekérése a session-ből
     */
    public static function getRefreshToken(): ?string
    {
        return Session::get(self::REFRESH_TOKEN_KEY);
    }

    /**
     * Token lejárati idő lekérése
     */
    public static function getTokenExpiresAt(): ?int
    {
        return Session::get(self::TOKEN_EXPIRES_AT_KEY);
    }

    /**
     * Felhasználói adatok lekérése a session-ből
     */
    public static function getUser(): ?array
    {
        return Session::get(self::USER_KEY);
    }

    /**
     * Tokenek tárolása a session-ben
     *
     * @param string $accessToken Access token
     * @param string $refreshToken Refresh token
     * @param int|null $expiresIn Token lejárati idő másodpercben (opcionális)
     */
    public static function setTokens(
        string $accessToken,
        string $refreshToken,
        ?int $expiresIn = null
    ): void {
        Session::set(self::ACCESS_TOKEN_KEY, $accessToken);
        Session::set(self::REFRESH_TOKEN_KEY, $refreshToken);

        if ($expiresIn !== null) {
            Session::set(self::TOKEN_EXPIRES_AT_KEY, time() + $expiresIn);
        }

        // Session ID regenerálása biztonsági okokból (session fixation védelem)
        Session::regenerate();
    }

    /**
     * Felhasználói adatok tárolása a session-ben
     *
     * @param array $user Felhasználói adatok (id, email, name, permissions)
     */
    public static function setUser(array $user): void
    {
        Session::set(self::USER_KEY, $user);
    }

    /**
     * Összes token és felhasználói adat törlése a session-ből
     */
    public static function clearTokens(): void
    {
        Session::remove(self::ACCESS_TOKEN_KEY);
        Session::remove(self::REFRESH_TOKEN_KEY);
        Session::remove(self::TOKEN_EXPIRES_AT_KEY);
        Session::remove(self::USER_KEY);
    }

    /**
     * Ellenőrzi, hogy van-e érvényes access token
     */
    public static function hasValidAccessToken(): bool
    {
        $accessToken = self::getAccessToken();
        if ($accessToken === null) {
            return false;
        }

        $expiresAt = self::getTokenExpiresAt();
        if ($expiresAt !== null && time() >= $expiresAt) {
            return false;
        }

        return true;
    }

    /**
     * Ellenőrzi, hogy van-e refresh token
     */
    public static function hasRefreshToken(): bool
    {
        return self::getRefreshToken() !== null;
    }

    /**
     * Ellenőrzi, hogy a felhasználó be van-e jelentkezve
     */
    public static function isAuthenticated(): bool
    {
        return self::getAccessToken() !== null && self::getUser() !== null;
    }

    /**
     * Access token frissítése (megújítás után)
     *
     * @param string $accessToken Új access token
     * @param string|null $refreshToken Új refresh token (opcionális)
     * @param int|null $expiresIn Token lejárati idő másodpercben (opcionális)
     */
    public static function updateTokens(
        string $accessToken,
        ?string $refreshToken = null,
        ?int $expiresIn = null
    ): void {
        Session::set(self::ACCESS_TOKEN_KEY, $accessToken);

        if ($refreshToken !== null) {
            Session::set(self::REFRESH_TOKEN_KEY, $refreshToken);
        }

        if ($expiresIn !== null) {
            Session::set(self::TOKEN_EXPIRES_AT_KEY, time() + $expiresIn);
        }
    }
}
