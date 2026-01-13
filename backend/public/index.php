<?php
/**
 * PHP Proxy fő router
 *
 * Útvonal feldolgozás, HTTP metódus kezelés, auth végpontok és proxy végpont.
 * Követelmények: 3.1, 3.3, 3.4
 */


declare(strict_types=1);

// Bootstrap betöltése
require_once dirname(__DIR__) . '/config/bootstrap.php';

// Src osztályok betöltése
require_once dirname(__DIR__) . '/src/Session.php';
require_once dirname(__DIR__) . '/src/Response.php';
require_once dirname(__DIR__) . '/src/TokenHandler.php';
require_once dirname(__DIR__) . '/src/TokenRefresher.php';
require_once dirname(__DIR__) . '/src/RequestForwarder.php';
require_once dirname(__DIR__) . '/src/CsrfProtection.php';
require_once dirname(__DIR__) . '/src/RateLimiter.php';
require_once dirname(__DIR__) . '/src/ErrorLogger.php';
require_once dirname(__DIR__) . '/src/PhpErrorHandler.php';

use App\Session;
use App\ErrorLogger;
use App\PhpErrorHandler;
use App\Response;
use App\TokenHandler;
use App\RequestForwarder;
use App\CsrfProtection;
use App\RateLimiter;

// PHP Error Handler regisztrálása (a lehető legkorábban)
$errorStorageFile = dirname(__DIR__) . '/data/errors.json';
$globalErrorLogger = new ErrorLogger($errorStorageFile);
PhpErrorHandler::register($globalErrorLogger);

// Session indítása
Session::start();

// Session lejárat ellenőrzése - 401 válasz lejárt session esetén
// Követelmények: 8.2
if (Session::isExpired()) {
    Response::unauthorized('Session expired');
}

// HTTP metódus és útvonal lekérése
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Query string eltávolítása az útvonalból
$path = parse_url($requestUri, PHP_URL_PATH);

// /api prefix eltávolítása ha van
$path = preg_replace('#^/api#', '', $path);
$path = '/' . ltrim($path, '/');

// OPTIONS kérések kezelése (CORS preflight)
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}


//trigger_error('Teszt hiba', E_USER_WARNING);


// Router
try {
    // Auth végpontok
    if (str_starts_with($path, '/auth/')) {
        handleAuthRoutes($path, $method);
    } elseif (str_starts_with($path, '/errors')) {
        // Error logging végpontok
        handleErrorRoutes($path, $method);
    } else {
        // Minden más kérés továbbítása a külső API-nak
        handleProxyRoute($path, $method);
    }
} catch (\Throwable $e) {
    if (\Config::isDebugMode()) {
        Response::serverError($e->getMessage());
    } else {
        Response::serverError('Internal server error');
    }
}

/**
 * Auth végpontok kezelése
 */
function handleAuthRoutes(string $path, string $method): void
{
    // Rate limiting auth végpontokhoz
    if (!RateLimiter::check()) {
        Response::tooManyRequests('Too many authentication attempts. Please try again later.');
    }

    switch ($path) {
        case '/auth/login':
            if ($method !== 'POST') {
                Response::error(405, 'METHOD_NOT_ALLOWED', 'Method not allowed');
            }
            handleLogin();
            break;

        case '/auth/logout':
            if ($method !== 'POST') {
                Response::error(405, 'METHOD_NOT_ALLOWED', 'Method not allowed');
            }
            handleLogout();
            break;

        case '/auth/me':
            if ($method !== 'GET') {
                Response::error(405, 'METHOD_NOT_ALLOWED', 'Method not allowed');
            }
            handleMe();
            break;

        case '/auth/status':
            if ($method !== 'GET') {
                Response::error(405, 'METHOD_NOT_ALLOWED', 'Method not allowed');
            }
            handleStatus();
            break;

        case '/auth/csrf':
            if ($method !== 'GET') {
                Response::error(405, 'METHOD_NOT_ALLOWED', 'Method not allowed');
            }
            handleCsrf();
            break;

        default:
            Response::notFound('Auth endpoint not found');
    }
}

/**
 * Bejelentkezés kezelése
 * Követelmények: 3.1, 3.2, 3.3
 */
function handleLogin(): void
{
    // Request body beolvasása
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data === null || !isset($data['email']) || !isset($data['password'])) {
        Response::badRequest('Email and password are required');
    }

    // Kérés továbbítása a külső API-nak
    $forwarder = new RequestForwarder();
    $response = $forwarder->post('/auth/login', [
        'email' => $data['email'],
        'password' => $data['password']
    ], [], false); // withAuth = false, mert még nincs token

    // Hálózati hiba kezelése
    if ($response['status'] === 0) {
        Response::serverError('Unable to connect to authentication service');
    }

    // Sikertelen bejelentkezés
    if ($response['status'] !== 200) {
        $errorMessage = $response['body']['message'] ?? 'Authentication failed';
        Response::error($response['status'], 'AUTH_FAILED', $errorMessage);
    }

    $body = $response['body'];

    // Tokenek ellenőrzése
    if (!isset($body['access_token']) || !isset($body['refresh_token'])) {
        Response::serverError('Invalid response from authentication service');
    }

    // Permissions ellenőrzése - üres permissions esetén sikertelen bejelentkezés
    // Biztonsági ellenőrzés: ha nincs jogosultság, nem engedjük be a felhasználót
    $userPermissions = $body['user']['permissions'] ?? [];
    if (empty($userPermissions)) {
        Response::error(401, 'AUTH_FAILED', 'No permissions assigned to user');
    }

    // Tokenek tárolása a session-ben (Követelmény: 3.2)
    TokenHandler::setTokens(
        $body['access_token'],
        $body['refresh_token'],
        $body['expires_in'] ?? null
    );

    // Felhasználói adatok tárolása
    if (isset($body['user'])) {
        TokenHandler::setUser($body['user']);
    }

    // CSRF token újragenerálása sikeres bejelentkezés után
    CsrfProtection::regenerateToken();

    // Sikeres válasz - tokenek NÉLKÜL (Követelmény: 3.3, 10.1)
    Response::success([
        'user' => $body['user'] ?? null,
        'csrf_token' => CsrfProtection::getToken()
    ]);
}

/**
 * Kijelentkezés kezelése
 * Követelmények: 3.4
 */
function handleLogout(): void
{
    // CSRF ellenőrzés
    if (!CsrfProtection::protect(['/auth/login'])) {
        Response::error(403, 'CSRF_ERROR', 'Invalid CSRF token');
    }

    // Ha van access token, értesítjük a külső API-t is
    if (TokenHandler::getAccessToken() !== null) {
        $forwarder = new RequestForwarder();
        // Nem várjuk meg a választ, csak elküldjük
        $forwarder->post('/auth/logout', [], [], true);
    }

    // Session és tokenek törlése
    TokenHandler::clearTokens();
    CsrfProtection::clearToken();
    Session::destroy();

    Response::success(['message' => 'Logged out successfully']);
}

/**
 * Aktuális felhasználó adatainak lekérése
 * Követelmények: 3.5
 */
function handleMe(): void
{
    // Ellenőrizzük, hogy be van-e jelentkezve
    if (!TokenHandler::isAuthenticated()) {
        Response::unauthorized('Not authenticated');
    }

    // Felhasználói adatok lekérése a külső API-tól (friss adatok)
    $forwarder = new RequestForwarder();
    $response = $forwarder->get('/auth/me');

    // Ha 401, a RequestForwarder már megpróbálta megújítani a tokent
    if ($response['status'] === 401) {
        Response::unauthorized('Session expired');
    }

    // Hálózati hiba
    if ($response['status'] === 0) {
        // Fallback: session-ből adjuk vissza az adatokat
        $user = TokenHandler::getUser();
        if ($user !== null) {
            Response::success(['user' => $user]);
        }
        Response::serverError('Unable to fetch user data');
    }

    // Sikertelen kérés
    if ($response['status'] !== 200) {
        Response::error($response['status'], 'FETCH_ERROR', 'Unable to fetch user data');
    }

    // Felhasználói adatok frissítése a session-ben
    $body = $response['body'];
    if (isset($body['user'])) {
        TokenHandler::setUser($body['user']);
    }

    // Válasz - tokenek kiszűrve (Követelmény: 10.1)
    Response::success(filterTokensFromResponse($body));
}

/**
 * Autentikációs állapot ellenőrzése
 * Követelmények: 8.3
 */
function handleStatus(): void
{
    $isAuthenticated = TokenHandler::isAuthenticated();
    $user = $isAuthenticated ? TokenHandler::getUser() : null;

    Response::success([
        'authenticated' => $isAuthenticated,
        'user' => $user,
        'csrf_token' => CsrfProtection::getToken()
    ]);
}

/**
 * CSRF token lekérése
 */
function handleCsrf(): void
{
    Response::success([
        'csrf_token' => CsrfProtection::getToken()
    ]);
}

/**
 * Error logging végpontok kezelése
 * Követelmények: 5.1, 5.4, 6.1
 */
function handleErrorRoutes(string $path, string $method): void
{
    // Error logger inicializálása
    $storageFile = dirname(__DIR__) . '/data/errors.json';
    $errorLogger = new ErrorLogger($storageFile);

    // Útvonal elemzése - /errors vagy /errors/{id}
    $pathParts = explode('/', trim($path, '/'));

    switch ($path) {
        case '/errors':
            if ($method === 'POST') {
                // Új hiba naplózása - nem igényel autentikációt
                handleLogError($errorLogger);
            } elseif ($method === 'GET') {
                // Hibák listázása - admin jogosultság szükséges
                requireAdminAuth();
                handleGetErrors($errorLogger);
            } else {
                Response::error(405, 'METHOD_NOT_ALLOWED', 'Method not allowed');
            }
            break;

        default:
            // /errors/{id} útvonal kezelése
            if (count($pathParts) === 2 && $pathParts[0] === 'errors') {
                if ($method !== 'GET') {
                    Response::error(405, 'METHOD_NOT_ALLOWED', 'Method not allowed');
                }
                requireAdminAuth();
                handleGetError($errorLogger, $pathParts[1]);
            } else {
                Response::notFound('Error endpoint not found');
            }
    }
}

/**
 * Új hiba naplózása
 * Követelmények: 5.1, 5.4
 */
function handleLogError(ErrorLogger $errorLogger): void
{
    // Request body beolvasása
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data === null) {
        Response::badRequest('Invalid JSON body');
    }

    // Alapértelmezett severity ha nincs megadva
    if (!isset($data['severity'])) {
        $data['severity'] = 'error';
    }

    // Alapértelmezett timestamp ha nincs megadva
    if (!isset($data['timestamp'])) {
        $data['timestamp'] = date('c');
    }

    try {
        $id = $errorLogger->log($data);
        Response::success(['id' => $id], 201);
    } catch (\InvalidArgumentException $e) {
        Response::badRequest($e->getMessage());
    }
}

/**
 * Hibák listázása szűrőkkel
 * Követelmények: 6.1
 */
function handleGetErrors(ErrorLogger $errorLogger): void
{
    $filters = [
        'type' => $_GET['type'] ?? null,
        'dateFrom' => $_GET['dateFrom'] ?? null,
        'dateTo' => $_GET['dateTo'] ?? null,
        'page' => $_GET['page'] ?? 1,
        'pageSize' => $_GET['pageSize'] ?? 20
    ];

    // Üres értékek eltávolítása
    $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

    $result = $errorLogger->getErrors($filters);

    Response::success($result);
}

/**
 * Egy hiba lekérdezése ID alapján
 * Követelmények: 6.3
 */
function handleGetError(ErrorLogger $errorLogger, string $id): void
{
    $error = $errorLogger->getError($id);

    if ($error === null) {
        Response::notFound('Error not found');
    }

    Response::success($error);
}

/**
 * Admin jogosultság ellenőrzése
 */
function requireAdminAuth(): void
{
    // Ellenőrizzük, hogy be van-e jelentkezve
    if (!TokenHandler::isAuthenticated()) {
        Response::unauthorized('Not authenticated');
    }

    // TODO: Admin jogosultság ellenőrzése a felhasználó szerepköre alapján
    // Jelenleg minden bejelentkezett felhasználó hozzáfér
}

/**
 * Proxy végpont - minden más kérés továbbítása
 * Követelmények: 2.1, 2.4, 2.5
 */
function handleProxyRoute(string $path, string $method): void
{
    // CSRF ellenőrzés állapotváltoztató kéréseknél
    if (CsrfProtection::isStateChangingRequest()) {
        if (!CsrfProtection::protect(['/auth/login'])) {
            Response::error(403, 'CSRF_ERROR', 'Invalid CSRF token');
        }
    }

    // Ellenőrizzük, hogy be van-e jelentkezve
    if (!TokenHandler::isAuthenticated()) {
        Response::unauthorized('Not authenticated');
    }

    $forwarder = new RequestForwarder();

    // Fájlfeltöltés kezelése
    if (!empty($_FILES)) {
        $data = $_POST;
        $response = $forwarder->upload($path, $_FILES, $data);
    } else {
        // Normál kérés
        $data = [];
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?? [];
        }

        $response = $forwarder->forward($method, $path, $data);
    }

    // Hálózati hiba
    if ($response['status'] === 0) {
        Response::serverError('Unable to connect to API service');
    }

    // Válasz továbbítása - tokenek kiszűrve (Követelmény: 10.1)
    $filteredBody = filterTokensFromResponse($response['body'] ?? []);

    // HTTP státusz kód alapján válasz
    if ($response['status'] >= 200 && $response['status'] < 300) {
        Response::success($filteredBody, $response['status']);
    } else {
        // Hiba válasz továbbítása
        $errorCode = $filteredBody['code'] ?? 'API_ERROR';
        $errorMessage = $filteredBody['message'] ?? 'API request failed';
        Response::error($response['status'], $errorCode, $errorMessage);
    }
}

/**
 * Tokenek kiszűrése a válaszból
 * Követelmények: 3.3, 10.1
 */
function filterTokensFromResponse(mixed $data): mixed
{
    if (!is_array($data)) {
        return $data;
    }

    $sensitiveKeys = [
        'access_token',
        'refresh_token',
        'token',
        'password',
        'secret',
        'api_key',
        'private_key'
    ];

    $filtered = [];
    foreach ($data as $key => $value) {
        // Érzékeny kulcsok kihagyása (csak string kulcsok esetén)
        if (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
            continue;
        }

        // Rekurzív szűrés tömbökre
        if (is_array($value)) {
            $filtered[$key] = filterTokensFromResponse($value);
        } else {
            $filtered[$key] = $value;
        }
    }

    return $filtered;
}
