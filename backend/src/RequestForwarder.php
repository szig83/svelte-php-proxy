<?php
/**
 * Request Forwarder osztály
 *
 * cURL alapú HTTP kérések továbbítása a külső API felé.
 * Automatikus token megújítás 401 válasz esetén.
 * Követelmények: 2.1, 2.4, 2.5, 4.1, 4.2, 4.3, 4.4
 */

declare(strict_types=1);

namespace App;

class RequestForwarder
{
    private string $baseUrl;
    private int $timeout;
    private ?TokenRefresher $tokenRefresher;
    private bool $isRetrying = false;

    /**
     * RequestForwarder konstruktor
     *
     * @param string|null $baseUrl Külső API alap URL (alapértelmezett: Config-ból)
     * @param int|null $timeout Timeout másodpercben (alapértelmezett: Config-ból)
     * @param TokenRefresher|null $tokenRefresher Token megújító (opcionális)
     */
    public function __construct(
        ?string $baseUrl = null,
        ?int $timeout = null,
        ?TokenRefresher $tokenRefresher = null
    ) {
        $this->baseUrl = $baseUrl ?? \Config::getExternalApiUrl();
        $this->timeout = $timeout ?? \Config::getExternalApiTimeout();
        $this->tokenRefresher = $tokenRefresher ?? new TokenRefresher($this->baseUrl, $this->timeout);
    }

    /**
     * HTTP kérés továbbítása a külső API felé
     * Automatikus token megújítás 401 válasz esetén.
     *
     * @param string $method HTTP metódus (GET, POST, PUT, DELETE, PATCH)
     * @param string $endpoint API végpont (pl. /users)
     * @param array $data Kérés adatok (POST/PUT/PATCH esetén)
     * @param array $headers Extra fejlécek
     * @param bool $withAuth Bearer token csatolása
     * @return array Válasz tömb ['status' => int, 'body' => mixed, 'headers' => array]
     */
    public function forward(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = [],
        bool $withAuth = true
    ): array {
        $response = $this->executeRequest($method, $endpoint, $data, $headers, $withAuth);

        // 401 válasz kezelése - automatikus token megújítás
        // Követelmények: 4.1, 4.2, 4.3
        if ($response['status'] === 401 && $withAuth && !$this->isRetrying) {
            $refreshResult = $this->handleUnauthorized();

            if ($refreshResult) {
                // Sikeres megújítás - eredeti kérés újrapróbálása
                $this->isRetrying = true;
                $response = $this->executeRequest($method, $endpoint, $data, $headers, $withAuth);
                $this->isRetrying = false;
            }
            // Ha a megújítás sikertelen, a handleUnauthorized már kezelte a session törlést
        }

        return $response;
    }

    /**
     * HTTP kérés végrehajtása (belső metódus)
     *
     * @param string $method HTTP metódus
     * @param string $endpoint API végpont
     * @param array $data Kérés adatok
     * @param array $headers Extra fejlécek
     * @param bool $withAuth Bearer token csatolása
     * @return array Válasz tömb
     */
    private function executeRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = [],
        bool $withAuth = true
    ): array {
        $url = $this->buildUrl($endpoint);
        $method = strtoupper($method);

        // cURL inicializálás
        $ch = curl_init();

        // Alap beállítások
        // SSL ellenőrzés beállítása környezet alapján
        $sslVerify = \Config::getSslVerify();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_HEADER => true,
        ]);

        // HTTP metódus beállítása
        $this->setMethod($ch, $method, $data);

        // Fejlécek összeállítása
        $requestHeaders = $this->buildHeaders($headers, $withAuth);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);


        // Kérés végrehajtása
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        if ($errno !== 0) {
            return [
                'status' => 0,
                'body' => null,
                'headers' => [],
                'error' => $error,
                'errno' => $errno
            ];
        }

        // Válasz feldolgozása
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);


        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        return [
            'status' => $httpCode,
            'body' => $this->parseBody($responseBody),
            'headers' => $this->parseHeaders($responseHeaders),
            'error' => null,
            'errno' => 0
        ];
    }

    /**
     * GET kérés
     */
    public function get(string $endpoint, array $headers = [], bool $withAuth = true): array
    {
        return $this->forward('GET', $endpoint, [], $headers, $withAuth);
    }

    /**
     * POST kérés
     */
    public function post(string $endpoint, array $data = [], array $headers = [], bool $withAuth = true): array
    {
        return $this->forward('POST', $endpoint, $data, $headers, $withAuth);
    }

    /**
     * PUT kérés
     */
    public function put(string $endpoint, array $data = [], array $headers = [], bool $withAuth = true): array
    {
        return $this->forward('PUT', $endpoint, $data, $headers, $withAuth);
    }

    /**
     * PATCH kérés
     */
    public function patch(string $endpoint, array $data = [], array $headers = [], bool $withAuth = true): array
    {
        return $this->forward('PATCH', $endpoint, $data, $headers, $withAuth);
    }

    /**
     * DELETE kérés
     */
    public function delete(string $endpoint, array $headers = [], bool $withAuth = true): array
    {
        return $this->forward('DELETE', $endpoint, [], $headers, $withAuth);
    }

    /**
     * 401 Unauthorized válasz kezelése - automatikus token megújítás
     * Követelmények: 4.1, 4.2, 4.3, 4.4
     *
     * @return bool Sikeres volt-e a megújítás
     */
    private function handleUnauthorized(): bool
    {
        // Ellenőrizzük, hogy van-e refresh token
        if (!TokenHandler::hasRefreshToken()) {
            // Nincs refresh token - session törlés
            $this->tokenRefresher->handleFailedRefresh();
            return false;
        }

        // Token megújítás megpróbálása
        $refreshSuccess = $this->tokenRefresher->refresh();

        if (!$refreshSuccess) {
            // Sikertelen megújítás - session törlés (Követelmény: 4.4)
            $this->tokenRefresher->handleFailedRefresh();
            return false;
        }

        // Sikeres megújítás - session frissítve (Követelmény: 4.3)
        return true;
    }

    /**
     * Fájlfeltöltés a külső API felé
     * Automatikus token megújítás 401 válasz esetén.
     *
     * @param string $endpoint API végpont
     * @param array $files Fájlok tömbje ($_FILES formátumban)
     * @param array $data Extra adatok
     * @param array $headers Extra fejlécek
     * @param bool $withAuth Bearer token csatolása
     * @return array Válasz tömb
     */
    public function upload(
        string $endpoint,
        array $files,
        array $data = [],
        array $headers = [],
        bool $withAuth = true
    ): array {
        $response = $this->executeUpload($endpoint, $files, $data, $headers, $withAuth);

        // 401 válasz kezelése - automatikus token megújítás
        if ($response['status'] === 401 && $withAuth && !$this->isRetrying) {
            $refreshResult = $this->handleUnauthorized();

            if ($refreshResult) {
                // Sikeres megújítás - eredeti kérés újrapróbálása
                $this->isRetrying = true;
                $response = $this->executeUpload($endpoint, $files, $data, $headers, $withAuth);
                $this->isRetrying = false;
            }
        }

        return $response;
    }

    /**
     * Fájlfeltöltés végrehajtása (belső metódus)
     *
     * @param string $endpoint API végpont
     * @param array $files Fájlok tömbje ($_FILES formátumban)
     * @param array $data Extra adatok
     * @param array $headers Extra fejlécek
     * @param bool $withAuth Bearer token csatolása
     * @return array Válasz tömb
     */
    private function executeUpload(
        string $endpoint,
        array $files,
        array $data = [],
        array $headers = [],
        bool $withAuth = true
    ): array {
        $url = $this->buildUrl($endpoint);

        // cURL inicializálás
        $ch = curl_init();

        // Alap beállítások
        // SSL ellenőrzés beállítása környezet alapján
        $sslVerify = \Config::getSslVerify();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_HEADER => true,
            CURLOPT_POST => true,
        ]);

        // Multipart form-data összeállítása
        $postFields = $this->buildMultipartData($files, $data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        // Fejlécek összeállítása (Content-Type nélkül, mert a cURL automatikusan beállítja)
        $requestHeaders = $this->buildUploadHeaders($headers, $withAuth);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        // Kérés végrehajtása
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        if ($errno !== 0) {
            return [
                'status' => 0,
                'body' => null,
                'headers' => [],
                'error' => $error,
                'errno' => $errno
            ];
        }

        // Válasz feldolgozása
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);


        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        return [
            'status' => $httpCode,
            'body' => $this->parseBody($responseBody),
            'headers' => $this->parseHeaders($responseHeaders),
            'error' => null,
            'errno' => 0
        ];
    }

    /**
     * Multipart form-data összeállítása fájlokból és adatokból
     *
     * @param array $files Fájlok tömbje ($_FILES formátumban)
     * @param array $data Extra adatok
     * @return array CURLFile objektumok és adatok tömbje
     */
    private function buildMultipartData(array $files, array $data): array
    {
        $postFields = [];

        // Fájlok feldolgozása
        foreach ($files as $fieldName => $file) {
            // Több fájl ugyanazon mező alatt
            if (isset($file['tmp_name']) && is_array($file['tmp_name'])) {
                foreach ($file['tmp_name'] as $index => $tmpName) {
                    if (!empty($tmpName) && is_uploaded_file($tmpName)) {
                        $curlFile = new \CURLFile(
                            $tmpName,
                            $file['type'][$index] ?? 'application/octet-stream',
                            $file['name'][$index] ?? 'file'
                        );
                        $postFields[$fieldName . '[' . $index . ']'] = $curlFile;
                    }
                }
            }
            // Egyetlen fájl
            elseif (isset($file['tmp_name']) && !empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
                $curlFile = new \CURLFile(
                    $file['tmp_name'],
                    $file['type'] ?? 'application/octet-stream',
                    $file['name'] ?? 'file'
                );
                $postFields[$fieldName] = $curlFile;
            }
        }

        // Extra adatok hozzáadása
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Tömb értékek kezelése
                foreach ($value as $index => $item) {
                    $postFields[$key . '[' . $index . ']'] = is_scalar($item) ? (string) $item : json_encode($item);
                }
            } else {
                $postFields[$key] = is_scalar($value) ? (string) $value : json_encode($value);
            }
        }

        return $postFields;
    }

    /**
     * Fejlécek összeállítása fájlfeltöltéshez
     * (Content-Type nélkül, mert a cURL automatikusan beállítja multipart/form-data-ra)
     */
    private function buildUploadHeaders(array $extraHeaders, bool $withAuth): array
    {
        $headers = [
            'Accept: application/json',
        ];

        // Bearer token csatolása ha szükséges
        if ($withAuth) {
            $accessToken = TokenHandler::getAccessToken();
            if ($accessToken !== null) {
                $headers[] = 'Authorization: Bearer ' . $accessToken;
            }
        }

        // Extra fejlécek hozzáadása
        foreach ($extraHeaders as $key => $value) {
            // Content-Type kihagyása, mert a cURL automatikusan beállítja
            if (strtolower($key) === 'content-type') {
                continue;
            }

            if (is_numeric($key)) {
                // Már formázott fejléc (pl. "X-Custom: value")
                if (!str_starts_with(strtolower($value), 'content-type:')) {
                    $headers[] = $value;
                }
            } else {
                // Kulcs-érték pár
                $headers[] = $key . ': ' . $value;
            }
        }

        return $headers;
    }

    /**
     * URL összeállítása
     */
    private function buildUrl(string $endpoint): string
    {
        // Ha az endpoint már teljes URL, használjuk azt
        if (str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            return $endpoint;
        }

        // Endpoint normalizálása
        $endpoint = '/' . ltrim($endpoint, '/');

        return rtrim($this->baseUrl, '/') . $endpoint;
    }

    /**
     * HTTP metódus beállítása a cURL handle-n
     */
    private function setMethod($ch, string $method, array $data): void
    {
        switch ($method) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;

            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }
    }

    /**
     * Fejlécek összeállítása
     */
    private function buildHeaders(array $extraHeaders, bool $withAuth): array
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        // Bearer token csatolása ha szükséges
        if ($withAuth) {
            $accessToken = TokenHandler::getAccessToken();
            if ($accessToken !== null) {
                $headers[] = 'Authorization: Bearer ' . $accessToken;
            }
        }

        // Extra fejlécek hozzáadása
        foreach ($extraHeaders as $key => $value) {
            if (is_numeric($key)) {
                // Már formázott fejléc (pl. "X-Custom: value")
                $headers[] = $value;
            } else {
                // Kulcs-érték pár
                $headers[] = $key . ': ' . $value;
            }
        }

        return $headers;
    }

    /**
     * Válasz body feldolgozása
     */
    private function parseBody(string $body): mixed
    {
        if (empty($body)) {
            return null;
        }

        $decoded = json_decode($body, true);

        // Ha nem valid JSON, visszaadjuk az eredeti stringet
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $body;
        }

        return $decoded;
    }

    /**
     * Válasz fejlécek feldolgozása
     */
    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            // HTTP státusz sor kihagyása
            if (str_starts_with($line, 'HTTP/')) {
                continue;
            }

            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $key = strtolower(trim($parts[0]));
                $value = trim($parts[1]);
                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}
