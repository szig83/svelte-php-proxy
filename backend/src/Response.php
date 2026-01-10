<?php
/**
 * Response osztály - API válaszok kezelése
 *
 * JSON válasz formázás, hiba és sikeres válaszok kezelése.
 * Követelmények: 7.3, 7.4
 */

declare(strict_types=1);

namespace App;

class Response
{
    /**
     * Sikeres válasz küldése
     *
     * @param mixed $data A válasz adatok
     * @param int $statusCode HTTP státusz kód (alapértelmezett: 200)
     */
    public static function success(mixed $data = null, int $statusCode = 200): void
    {
        self::send([
            'success' => true,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Hiba válasz küldése
     *
     * @param int $statusCode HTTP státusz kód
     * @param string $code Hibakód (pl. 'UNAUTHORIZED', 'VALIDATION_ERROR')
     * @param string $message Hibaüzenet
     * @param array|null $details Opcionális részletek (pl. validációs hibák)
     */
    public static function error(
        int $statusCode,
        string $code,
        string $message,
        ?array $details = null
    ): void {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];

        if ($details !== null) {
            $response['error']['details'] = $details;
        }

        self::send($response, $statusCode);
    }

    /**
     * 400 Bad Request válasz
     */
    public static function badRequest(string $message, ?array $details = null): void
    {
        self::error(400, 'VALIDATION_ERROR', $message, $details);
    }

    /**
     * 401 Unauthorized válasz
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error(401, 'UNAUTHORIZED', $message);
    }

    /**
     * 403 Forbidden válasz
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error(403, 'FORBIDDEN', $message);
    }

    /**
     * 404 Not Found válasz
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error(404, 'NOT_FOUND', $message);
    }

    /**
     * 429 Too Many Requests válasz
     */
    public static function tooManyRequests(string $message = 'Too many requests'): void
    {
        self::error(429, 'RATE_LIMITED', $message);
    }

    /**
     * 500 Internal Server Error válasz
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error(500, 'SERVER_ERROR', $message);
    }

    /**
     * JSON válasz küldése
     *
     * @param array $data A válasz adatok
     * @param int $statusCode HTTP státusz kód
     */
    private static function send(array $data, int $statusCode): void
    {
        // HTTP státusz kód beállítása
        http_response_code($statusCode);

        // JSON content type header
        header('Content-Type: application/json; charset=utf-8');

        // Cache tiltása API válaszoknál
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        // JSON kimenet
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Külső API válasz továbbítása a frontend felé
     * Token adatok kiszűrésével
     *
     * @param array $apiResponse A külső API válasza
     * @param int $statusCode HTTP státusz kód
     */
    public static function forwardApiResponse(array $apiResponse, int $statusCode = 200): void
    {
        // Token adatok kiszűrése a válaszból (biztonsági okokból)
        $filteredResponse = self::filterSensitiveData($apiResponse);

        self::send([
            'success' => $statusCode >= 200 && $statusCode < 300,
            'data' => $filteredResponse
        ], $statusCode);
    }

    /**
     * Érzékeny adatok kiszűrése a válaszból
     *
     * @param array $data A szűrendő adatok
     * @return array A szűrt adatok
     */
    private static function filterSensitiveData(array $data): array
    {
        $sensitiveKeys = [
            'access_token',
            'refresh_token',
            'token',
            'password',
            'secret'
        ];

        $filtered = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys, true)) {
                continue; // Kihagyjuk az érzékeny adatokat
            }

            if (is_array($value)) {
                $filtered[$key] = self::filterSensitiveData($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
