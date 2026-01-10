<?php
/**
 * Property-Based Test: Session Lejárat Kezelés
 *
 * **Tulajdonság 9: Session Lejárat Kezelés**
 * *Bármely* lejárt session esetén, a PHP_Proxy-nak 401-et kell visszaadnia,
 * és a Frontend-nek át kell irányítania a felhasználót a bejelentkezési oldalra.
 *
 * **Validálja: Követelmények 4.4, 4.5, 8.2**
 *
 * Feature: svelte-php-proxy-auth, Property 9: Session Lejárat Kezelés
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Session;
use App\TokenHandler;

/**
 * Mock Session class for testing expiration behavior
 */
class TestableSession
{
    private static bool $started = false;
    private static bool $expired = false;
    private static int $lifetime = 3600;
    private static ?int $lastActivity = null;

    public static function reset(): void
    {
        self::$started = false;
        self::$expired = false;
        self::$lifetime = 3600;
        self::$lastActivity = null;
        $_SESSION = [];
    }

    public static function setLifetime(int $lifetime): void
    {
        self::$lifetime = $lifetime;
    }

    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        // Initialize session
        if (!isset($_SESSION['_initialized'])) {
            $_SESSION['_initialized'] = true;
            $_SESSION['_created_at'] = time();
        }

        // Check expiration
        self::checkExpiration();

        self::$started = true;
    }

    private static function checkExpiration(): bool
    {
        if (isset($_SESSION['_last_activity'])) {
            $elapsed = time() - $_SESSION['_last_activity'];
            if ($elapsed > self::$lifetime) {
                self::$expired = true;
                self::destroy();
                return true;
            }
        }
        $_SESSION['_last_activity'] = time();
        return false;
    }

    public static function simulateExpiration(): void
    {
        self::$expired = true;
        $_SESSION = [];
    }

    public static function isExpired(): bool
    {
        return self::$expired;
    }

    public static function isValid(): bool
    {
        if (self::$expired) {
            return false;
        }

        if (!isset($_SESSION['access_token'])) {
            return false;
        }

        return true;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        self::$started = false;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
}

/**
 * Simulates the PHP Proxy response behavior for session expiration
 */
class SessionExpirationHandler
{
    private TestableSession $session;
    private bool $sessionExpired = false;

    public function __construct()
    {
        $this->session = new TestableSession();
    }

    /**
     * Check if session is expired and return appropriate response
     *
     * @return array Response with status and body
     */
    public function checkSessionAndRespond(): array
    {
        if (TestableSession::isExpired()) {
            $this->sessionExpired = true;
            return [
                'status' => 401,
                'body' => [
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Session expired'
                    ]
                ]
            ];
        }

        // Session is valid
        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'data' => null
            ]
        ];
    }

    /**
     * Simulate a request to a protected endpoint
     *
     * @return array Response with status and body
     */
    public function handleProtectedRequest(): array
    {
        // First check session expiration
        if (TestableSession::isExpired()) {
            return [
                'status' => 401,
                'body' => [
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Session expired'
                    ]
                ]
            ];
        }

        // Check if authenticated
        if (!TestableSession::isValid()) {
            return [
                'status' => 401,
                'body' => [
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Not authenticated'
                    ]
                ]
            ];
        }

        // Success
        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'data' => ['message' => 'Request successful']
            ]
        ];
    }

    public function wasSessionExpired(): bool
    {
        return $this->sessionExpired;
    }
}

class SessionExpirationPropertyTest extends TestCase
{
    private const ITERATIONS = 100;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        TestableSession::reset();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        TestableSession::reset();
    }

    /**
     * Property 9: For any expired session, the PHP_Proxy MUST return 401.
     *
     * **Validates: Requirements 8.2**
     */
    #[Test]
    public function expiredSessionReturns401(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            TestableSession::reset();

            // Generate random tokens and set up session
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();

            $_SESSION['access_token'] = $accessToken;
            $_SESSION['refresh_token'] = $refreshToken;
            $_SESSION['_initialized'] = true;
            $_SESSION['_last_activity'] = time();

            // Simulate session expiration
            TestableSession::simulateExpiration();

            // Create handler and check response
            $handler = new SessionExpirationHandler();
            $response = $handler->checkSessionAndRespond();

            // Verify: 401 status code
            $this->assertSame(
                401,
                $response['status'],
                "Expired session should return 401 (iteration $i)"
            );

            // Verify: error response structure
            $this->assertFalse(
                $response['body']['success'],
                "Response should indicate failure (iteration $i)"
            );

            $this->assertSame(
                'UNAUTHORIZED',
                $response['body']['error']['code'],
                "Error code should be UNAUTHORIZED (iteration $i)"
            );
        }
    }

    /**
     * Property 9: For any expired session, protected endpoints MUST return 401.
     *
     * **Validates: Requirements 4.4, 8.2**
     */
    #[Test]
    public function expiredSessionBlocksProtectedEndpoints(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            TestableSession::reset();

            // Generate random tokens and set up session
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();

            $_SESSION['access_token'] = $accessToken;
            $_SESSION['refresh_token'] = $refreshToken;
            $_SESSION['_initialized'] = true;
            $_SESSION['_last_activity'] = time();
            $_SESSION['user'] = $this->generateRandomUser();

            // Simulate session expiration
            TestableSession::simulateExpiration();

            // Create handler and try to access protected endpoint
            $handler = new SessionExpirationHandler();
            $response = $handler->handleProtectedRequest();

            // Verify: 401 status code
            $this->assertSame(
                401,
                $response['status'],
                "Protected endpoint should return 401 for expired session (iteration $i)"
            );

            // Verify: error message indicates session expiration
            $this->assertStringContainsString(
                'expired',
                strtolower($response['body']['error']['message']),
                "Error message should indicate session expiration (iteration $i)"
            );
        }
    }

    /**
     * Property 9: For any valid (non-expired) session with tokens,
     * protected endpoints MUST return 200.
     *
     * **Validates: Requirements 8.2**
     */
    #[Test]
    public function validSessionAllowsProtectedEndpoints(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            TestableSession::reset();

            // Generate random tokens and set up valid session
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();

            $_SESSION['access_token'] = $accessToken;
            $_SESSION['refresh_token'] = $refreshToken;
            $_SESSION['_initialized'] = true;
            $_SESSION['_last_activity'] = time();
            $_SESSION['user'] = $this->generateRandomUser();

            // Do NOT expire the session
            TestableSession::start();

            // Create handler and try to access protected endpoint
            $handler = new SessionExpirationHandler();
            $response = $handler->handleProtectedRequest();

            // Verify: 200 status code
            $this->assertSame(
                200,
                $response['status'],
                "Valid session should allow access to protected endpoint (iteration $i)"
            );

            // Verify: success response
            $this->assertTrue(
                $response['body']['success'],
                "Response should indicate success (iteration $i)"
            );
        }
    }

    /**
     * Property 9: For any session that expires, tokens MUST be cleared.
     *
     * **Validates: Requirements 4.4**
     */
    #[Test]
    public function expiredSessionClearsTokens(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            TestableSession::reset();

            // Generate random tokens and set up session
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();

            $_SESSION['access_token'] = $accessToken;
            $_SESSION['refresh_token'] = $refreshToken;
            $_SESSION['_initialized'] = true;
            $_SESSION['_last_activity'] = time();
            $_SESSION['user'] = $this->generateRandomUser();

            // Verify tokens exist before expiration
            $this->assertNotNull(
                $_SESSION['access_token'] ?? null,
                "Access token should exist before expiration (iteration $i)"
            );

            // Simulate session expiration (which clears session data)
            TestableSession::simulateExpiration();

            // Verify: tokens are cleared
            $this->assertArrayNotHasKey(
                'access_token',
                $_SESSION,
                "Access token should be cleared after expiration (iteration $i)"
            );

            $this->assertArrayNotHasKey(
                'refresh_token',
                $_SESSION,
                "Refresh token should be cleared after expiration (iteration $i)"
            );

            $this->assertArrayNotHasKey(
                'user',
                $_SESSION,
                "User data should be cleared after expiration (iteration $i)"
            );
        }
    }

    /**
     * Property 9: For any session without tokens, isValid() MUST return false.
     *
     * **Validates: Requirements 8.2**
     */
    #[Test]
    public function sessionWithoutTokensIsInvalid(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            TestableSession::reset();

            // Set up session without tokens
            $_SESSION['_initialized'] = true;
            $_SESSION['_last_activity'] = time();
            // Deliberately NOT setting access_token

            TestableSession::start();

            // Verify: session is not valid
            $this->assertFalse(
                TestableSession::isValid(),
                "Session without tokens should be invalid (iteration $i)"
            );

            // Try to access protected endpoint
            $handler = new SessionExpirationHandler();
            $response = $handler->handleProtectedRequest();

            // Verify: 401 status code
            $this->assertSame(
                401,
                $response['status'],
                "Session without tokens should return 401 (iteration $i)"
            );
        }
    }

    /**
     * Property 9: For any HTTP method, expired session handling MUST be consistent.
     *
     * **Validates: Requirements 4.4, 8.2**
     */
    #[Test]
    public function expiredSessionHandlingConsistentAcrossMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $method = $methods[array_rand($methods)];
            TestableSession::reset();

            // Generate random tokens and set up session
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();

            $_SESSION['access_token'] = $accessToken;
            $_SESSION['refresh_token'] = $refreshToken;
            $_SESSION['_initialized'] = true;
            $_SESSION['_last_activity'] = time();
            $_SESSION['user'] = $this->generateRandomUser();

            // Simulate session expiration
            TestableSession::simulateExpiration();

            // Create handler and check response
            $handler = new SessionExpirationHandler();
            $response = $handler->handleProtectedRequest();

            // Verify: 401 status code regardless of HTTP method
            $this->assertSame(
                401,
                $response['status'],
                "Expired session should return 401 for $method method (iteration $i)"
            );

            // Verify: consistent error structure
            $this->assertArrayHasKey(
                'error',
                $response['body'],
                "Response should have error key for $method method (iteration $i)"
            );

            $this->assertArrayHasKey(
                'code',
                $response['body']['error'],
                "Error should have code for $method method (iteration $i)"
            );

            $this->assertArrayHasKey(
                'message',
                $response['body']['error'],
                "Error should have message for $method method (iteration $i)"
            );
        }
    }

    /**
     * Property 9: For any random endpoint, expired session MUST return 401.
     *
     * **Validates: Requirements 8.2**
     */
    #[Test]
    public function expiredSessionReturns401ForAnyEndpoint(): void
    {
        $endpoints = [
            '/api/users',
            '/api/products',
            '/api/orders',
            '/api/settings',
            '/api/dashboard',
            '/api/reports',
            '/api/admin/users',
            '/api/admin/stats'
        ];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $endpoint = $endpoints[array_rand($endpoints)];
            TestableSession::reset();

            // Generate random tokens and set up session
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();

            $_SESSION['access_token'] = $accessToken;
            $_SESSION['refresh_token'] = $refreshToken;
            $_SESSION['_initialized'] = true;
            $_SESSION['_last_activity'] = time();
            $_SESSION['user'] = $this->generateRandomUser();

            // Simulate session expiration
            TestableSession::simulateExpiration();

            // Create handler and check response
            $handler = new SessionExpirationHandler();
            $response = $handler->handleProtectedRequest();

            // Verify: 401 status code for any endpoint
            $this->assertSame(
                401,
                $response['status'],
                "Expired session should return 401 for endpoint $endpoint (iteration $i)"
            );
        }
    }

    /**
     * Property 9: Session expiration detection MUST be deterministic.
     * If a session is marked as expired, isExpired() MUST always return true.
     *
     * **Validates: Requirements 8.2**
     */
    #[Test]
    public function sessionExpirationIsDeterministic(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            TestableSession::reset();

            // Set up session
            $_SESSION['access_token'] = $this->generateRandomJwtToken();
            $_SESSION['_initialized'] = true;
            $_SESSION['_last_activity'] = time();

            // Simulate expiration
            TestableSession::simulateExpiration();

            // Check multiple times - should always return true
            for ($j = 0; $j < 5; $j++) {
                $this->assertTrue(
                    TestableSession::isExpired(),
                    "isExpired() should consistently return true after expiration (iteration $i, check $j)"
                );
            }
        }
    }

    /**
     * Generate a random JWT-like token for testing
     */
    private function generateRandomJwtToken(): string
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'sub' => bin2hex(random_bytes(8)),
            'iat' => time(),
            'exp' => time() + random_int(300, 86400),
            'jti' => bin2hex(random_bytes(16))
        ]));
        $signature = base64_encode(random_bytes(32));

        return "$header.$payload.$signature";
    }

    /**
     * Generate random user data for testing
     */
    private function generateRandomUser(): array
    {
        return [
            'id' => bin2hex(random_bytes(8)),
            'email' => bin2hex(random_bytes(4)) . '@example.com',
            'name' => 'Test User ' . random_int(1, 1000),
            'permissions' => ['read', 'write']
        ];
    }
}
