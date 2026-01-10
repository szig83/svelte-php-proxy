<?php
/**
 * CSRF védelem osztály
 *
 * CSRF token generálás és validáció állapotváltoztató kéréseknél.
 * Követelmények: 8.5
 */

declare(strict_types=1);

namespace App;

class CsrfProtection
{
    private const TOKEN_KEY = 'csrf_token';
    private const TOKEN_HEADER = 'X-CSRF-Token';
    private const TOKEN_LENGTH = 32;

    /**
     * Állapotváltoztató HTTP metódusok, amelyeknél CSRF ellenőrzés szükséges
     */
    private const STATE_CHANGING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * CSRF token generálása és tárolása a session-ben
     *
     * @return string A generált token
     */
    public static function generateToken(): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        Session::set(self::TOKEN_KEY, $token);
        return $token;
    }

    /**
     * Aktuális CSRF token lekérése
     * Ha nincs token, generál egyet
     *
     * @return string A CSRF token
     */
    public static function getToken(): string
    {
        $token = Session::get(self::TOKEN_KEY);

        if ($token === null) {
            $token = self::generateToken();
        }

        return $token;
    }

    /**
     * CSRF token validálása
     *
     * @param string $token Az ellenőrizendő token
     * @return bool True ha a token érvényes
     */
    public static function validateToken(string $token): bool
    {
        $storedToken = Session::get(self::TOKEN_KEY);

        if ($storedToken === null || $token === '') {
            return false;
        }

        // Időzítés-biztos összehasonlítás
        return hash_equals($storedToken, $token);
    }

    /**
     * CSRF token lekérése a kérésből (header vagy body)
     *
     * @return string|null A token vagy null ha nincs
     */
    public static function getTokenFromRequest(): ?string
    {
        // Először a header-ből próbáljuk
        $headerToken = self::getTokenFromHeader();
        if ($headerToken !== null) {
            return $headerToken;
        }

        // Ha nincs header-ben, próbáljuk a body-ból (POST adatok)
        return self::getTokenFromBody();
    }

    /**
     * CSRF token lekérése a HTTP header-ből
     */
    private static function getTokenFromHeader(): ?string
    {
        // Apache és nginx különböző módon kezeli a custom header-eket
        $headerName = 'HTTP_' . str_replace('-', '_', strtoupper(self::TOKEN_HEADER));

        if (isset($_SERVER[$headerName])) {
            return $_SERVER[$headerName];
        }

        // Alternatív header név (néhány szerver így küldi)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        return null;
    }

    /**
     * CSRF token lekérése a request body-ból
     */
    private static function getTokenFromBody(): ?string
    {
        // POST adatokból
        if (isset($_POST['_csrf_token'])) {
            return $_POST['_csrf_token'];
        }

        // JSON body-ból
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            if (isset($data['_csrf_token'])) {
                return $data['_csrf_token'];
            }
        }

        return null;
    }

    /**
     * Ellenőrzi, hogy a kérés állapotváltoztató-e
     *
     * @return bool True ha állapotváltoztató metódus
     */
    public static function isStateChangingRequest(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return in_array(strtoupper($method), self::STATE_CHANGING_METHODS, true);
    }

    /**
     * CSRF védelem végrehajtása
     * Állapotváltoztató kéréseknél ellenőrzi a tokent
     *
     * @param array $excludedPaths Kizárt útvonalak (pl. login)
     * @return bool True ha a kérés átment az ellenőrzésen
     */
    public static function protect(array $excludedPaths = []): bool
    {
        // Nem állapotváltoztató kérések átmennek
        if (!self::isStateChangingRequest()) {
            return true;
        }

        // Kizárt útvonalak ellenőrzése
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        $currentPath = parse_url($currentPath, PHP_URL_PATH);

        foreach ($excludedPaths as $excludedPath) {
            if (strpos($currentPath, $excludedPath) !== false) {
                return true;
            }
        }

        // Token validálás
        $token = self::getTokenFromRequest();

        if ($token === null) {
            return false;
        }

        return self::validateToken($token);
    }

    /**
     * Token újragenerálása (pl. sikeres bejelentkezés után)
     *
     * @return string Az új token
     */
    public static function regenerateToken(): string
    {
        return self::generateToken();
    }

    /**
     * Token törlése a session-ből
     */
    public static function clearToken(): void
    {
        Session::remove(self::TOKEN_KEY);
    }

    /**
     * CSRF header név lekérése (frontend számára)
     */
    public static function getHeaderName(): string
    {
        return self::TOKEN_HEADER;
    }
}
