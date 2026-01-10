<?php
/**
 * Token Refresher osztály
 *
 * Automatikus token megújítás logika implementálása.
 * Követelmények: 4.1, 4.2, 4.3, 4.4
 */

declare(strict_types=1);

namespace App;

class TokenRefresher
{
    private string $baseUrl;
    private int $timeout;
    private string $refreshEndpoint;

    /**
     * TokenRefresher konstruktor
     *
     * @param string|null $baseUrl Külső API alap URL
     * @param int|null $timeout Timeout másodpercben
     * @param string $refreshEndpoint Token megújítás végpont
     */
    public function __construct(
        ?string $baseUrl = null,
        ?int $timeout = null,
        string $refreshEndpoint = '/auth/refresh'
    ) {
        $this->baseUrl = $baseUrl ?? \Config::getExternalApiUrl();
        $this->timeout = $timeout ?? \Config::getExternalApiTimeout();
        $this->refreshEndpoint = $refreshEndpoint;
    }

    /**
     * Token megújítás végrehajtása
     *
     * @return bool Sikeres volt-e a megújítás
     */
    public function refresh(): bool
    {
        $refreshToken = TokenHandler::getRefreshToken();

        // Ha nincs refresh token, nem tudunk megújítani
        if ($refreshToken === null) {
            return false;
        }

        // Refresh kérés küldése a külső API-nak
        $response = $this->sendRefreshRequest($refreshToken);

        // Ha sikertelen a kérés (hálózati hiba)
        if ($response['status'] === 0) {
            return false;
        }

        // Ha a külső API elutasította a refresh tokent
        if ($response['status'] !== 200) {
            return false;
        }

        // Sikeres megújítás - tokenek frissítése a session-ben
        $body = $response['body'];

        if (!isset($body['access_token'])) {
            return false;
        }

        // Tokenek frissítése
        TokenHandler::updateTokens(
            $body['access_token'],
            $body['refresh_token'] ?? null,
            $body['expires_in'] ?? null
        );

        return true;
    }

    /**
     * Refresh kérés küldése a külső API-nak
     *
     * @param string $refreshToken A refresh token
     * @return array Válasz tömb ['status' => int, 'body' => mixed]
     */
    private function sendRefreshRequest(string $refreshToken): array
    {
        $url = $this->buildUrl($this->refreshEndpoint);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'refresh_token' => $refreshToken
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);

        if ($errno !== 0) {
            curl_close($ch);
            return [
                'status' => 0,
                'body' => null,
                'error' => curl_error($ch)
            ];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $body = null;
        }

        return [
            'status' => $httpCode,
            'body' => $body
        ];
    }

    /**
     * URL összeállítása
     */
    private function buildUrl(string $endpoint): string
    {
        if (str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            return $endpoint;
        }

        $endpoint = '/' . ltrim($endpoint, '/');
        return rtrim($this->baseUrl, '/') . $endpoint;
    }

    /**
     * Sikertelen megújítás kezelése - session törlés
     * Követelmények: 4.4
     */
    public function handleFailedRefresh(): void
    {
        // Tokenek törlése a session-ből
        TokenHandler::clearTokens();

        // Session megsemmisítése
        Session::destroy();
    }
}
