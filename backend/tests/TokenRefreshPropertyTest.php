<?php
/**
 * Property-Based Test: Automatikus Token Megújítás
 *
 * **Tulajdonság 3: Automatikus Token Megújítás**
 * *Bármely* 401-es válasz esetén a Külső_API-tól, ha van érvényes refresh_token
 * a session-ben, a PHP_Proxy-nak meg kell próbálnia megújítani az access_token-t,
 * és sikeres megújítás esetén újra kell próbálnia az eredeti kérést.
 *
 * **Validálja: Követelmények 4.1, 4.2**
 *
 * Feature: svelte-php-proxy-auth, Property 3: Automatikus Token Megújítás
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\TokenHandler;
use App\Session;

/**
 * Mock TokenRefresher for testing refresh logic
 */
class MockTokenRefresher
{
    private bool $shouldSucceed;
    private ?string $newAccessToken;
    private ?string $newRefreshToken;
    private int $refreshAttempts = 0;
    private bool $handleFailedCalled = false;

    public function __construct(
        bool $shouldSucceed = true,
        ?string $newAccessToken = null,
        ?string $newRefreshToken = null
    ) {
        $this->shouldSucceed = $shouldSucceed;
        $this->newAccessToken = $newAccessToken;
        $this->newRefreshToken = $newRefreshToken;
    }

    public function refresh(): bool
    {
        $this->refreshAttempts++;

        $refreshToken = TokenHandler::getRefreshToken();
        if ($refreshToken === null) {
            return false;
        }

        if (!$this->shouldSucceed) {
            return false;
        }

        // Simulate successful refresh - update tokens
        if ($this->newAccessToken !== null) {
            TokenHandler::updateTokens(
                $this->newAccessToken,
                $this->newRefreshToken,
                3600
            );
        }

        return true;
    }

    public function handleFailedRefresh(): void
    {
        $this->handleFailedCalled = true;
        TokenHandler::clearTokens();
    }

    public function getRefreshAttempts(): int
    {
        return $this->refreshAttempts;
    }

    public function wasHandleFailedCalled(): bool
    {
        return $this->handleFailedCalled;
    }
}

/**
 * Testable RequestForwarder that doesn't make actual HTTP calls
 */
class TestableRequestForwarder
{
    private MockTokenRefresher $tokenRefresher;
    private bool $isRetrying = false;
    private array $responses;
    private int $requestCount = 0;

    public function __construct(MockTokenRefresher $tokenRefresher, array $responses = [])
    {
        $this->tokenRefresher = $tokenRefresher;
        $this->responses = $responses;
    }

    /**
     * Simulate forward request with automatic token refresh on 401
     */
    public function forward(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = [],
        bool $withAuth = true
    ): array {
        $response = $this->executeRequest($method, $endpoint, $data, $headers, $withAuth);

        // 401 response handling - automatic token refresh
        if ($response['status'] === 401 && $withAuth && !$this->isRetrying) {
            $refreshResult = $this->handleUnauthorized();

            if ($refreshResult) {
                // Successful refresh - retry original request
                $this->isRetrying = true;
                $response = $this->executeRequest($method, $endpoint, $data, $headers, $withAuth);
                $this->isRetrying = false;
            }
        }

        return $response;
    }

    private function executeRequest(
        string $method,
        string $endpoint,
        array $data,
        array $headers,
        bool $withAuth
    ): array {
        $this->requestCount++;
        $index = min($this->requestCount - 1, count($this->responses) - 1);
        return $this->responses[$index] ?? ['status' => 200, 'body' => null];
    }

    private function handleUnauthorized(): bool
    {
        if (!TokenHandler::hasRefreshToken()) {
            $this->tokenRefresher->handleFailedRefresh();
            return false;
        }

        $refreshSuccess = $this->tokenRefresher->refresh();

        if (!$refreshSuccess) {
            $this->tokenRefresher->handleFailedRefresh();
            return false;
        }

        return true;
    }

    public function getRequestCount(): int
    {
        return $this->requestCount;
    }
}

class TokenRefreshPropertyTest extends TestCase
{
    private const ITERATIONS = 100;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_SERVER = [];
    }

    /**
     * Property 3: For any 401 response with a valid refresh token in session,
     * the system MUST attempt to refresh the access token.
     *
     * **Validates: Requirements 4.1**
     */
    #[Test]
    public function refreshIsAttemptedOn401WithRefreshToken(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $_SESSION = [];

            // Generate random tokens
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();
            $newAccessToken = $this->generateRandomJwtToken();

            // Store tokens in session
            TokenHandler::setTokens($accessToken, $refreshToken);

            // Create mock that tracks refresh attempts
            $mockRefresher = new MockTokenRefresher(true, $newAccessToken);

            // Simulate 401 response followed by 200 on retry
            $forwarder = new TestableRequestForwarder($mockRefresher, [
                ['status' => 401, 'body' => ['error' => 'Unauthorized']],
                ['status' => 200, 'body' => ['success' => true]]
            ]);

            // Execute request
            $response = $forwarder->forward('GET', '/api/test');

            // Verify: refresh was attempted exactly once
            $this->assertSame(
                1,
                $mockRefresher->getRefreshAttempts(),
                "Refresh should be attempted exactly once on 401 (iteration $i)"
            );

            // Verify: request was retried after successful refresh
            $this->assertSame(
                2,
                $forwarder->getRequestCount(),
                "Request should be retried after successful refresh (iteration $i)"
            );

            // Verify: final response is successful
            $this->assertSame(
                200,
                $response['status'],
                "Final response should be 200 after successful refresh (iteration $i)"
            );
        }
    }

    /**
     * Property 3: For any 401 response, if refresh succeeds,
     * the new access token MUST be stored in session.
     *
     * **Validates: Requirements 4.2**
     */
    #[Test]
    public function successfulRefreshUpdatesSessionTokens(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $_SESSION = [];

            // Generate random tokens
            $originalAccessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();
            $newAccessToken = $this->generateRandomJwtToken();
            $newRefreshToken = $this->generateRandomJwtToken();

            // Store original tokens
            TokenHandler::setTokens($originalAccessToken, $refreshToken);

            // Create mock that returns new tokens
            $mockRefresher = new MockTokenRefresher(true, $newAccessToken, $newRefreshToken);

            // Simulate 401 followed by 200
            $forwarder = new TestableRequestForwarder($mockRefresher, [
                ['status' => 401, 'body' => null],
                ['status' => 200, 'body' => null]
            ]);

            // Execute request
            $forwarder->forward('GET', '/api/test');

            // Verify: new access token is stored
            $this->assertSame(
                $newAccessToken,
                TokenHandler::getAccessToken(),
                "New access token should be stored after successful refresh (iteration $i)"
            );

            // Verify: new refresh token is stored (if provided)
            $this->assertSame(
                $newRefreshToken,
                TokenHandler::getRefreshToken(),
                "New refresh token should be stored after successful refresh (iteration $i)"
            );
        }
    }

    /**
     * Property 3: For any 401 response WITHOUT a refresh token,
     * no refresh should be attempted and session should be cleared.
     *
     * **Validates: Requirements 4.1, 4.4**
     */
    #[Test]
    public function noRefreshAttemptWithoutRefreshToken(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $_SESSION = [];

            // Only set access token, no refresh token
            $accessToken = $this->generateRandomJwtToken();
            $_SESSION['access_token'] = $accessToken;

            // Create mock
            $mockRefresher = new MockTokenRefresher(true);

            // Simulate 401 response
            $forwarder = new TestableRequestForwarder($mockRefresher, [
                ['status' => 401, 'body' => null]
            ]);

            // Execute request
            $response = $forwarder->forward('GET', '/api/test');

            // Verify: no refresh attempt was made
            $this->assertSame(
                0,
                $mockRefresher->getRefreshAttempts(),
                "No refresh should be attempted without refresh token (iteration $i)"
            );

            // Verify: handleFailedRefresh was called
            $this->assertTrue(
                $mockRefresher->wasHandleFailedCalled(),
                "handleFailedRefresh should be called when no refresh token (iteration $i)"
            );

            // Verify: tokens are cleared
            $this->assertNull(
                TokenHandler::getAccessToken(),
                "Access token should be cleared (iteration $i)"
            );
        }
    }

    /**
     * Property 3: For any 401 response where refresh FAILS,
     * the session MUST be cleared.
     *
     * **Validates: Requirements 4.4**
     */
    #[Test]
    public function failedRefreshClearsSession(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $_SESSION = [];

            // Generate random tokens
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();

            // Store tokens
            TokenHandler::setTokens($accessToken, $refreshToken);

            // Create mock that fails refresh
            $mockRefresher = new MockTokenRefresher(false);

            // Simulate 401 response
            $forwarder = new TestableRequestForwarder($mockRefresher, [
                ['status' => 401, 'body' => null]
            ]);

            // Execute request
            $response = $forwarder->forward('GET', '/api/test');

            // Verify: refresh was attempted
            $this->assertSame(
                1,
                $mockRefresher->getRefreshAttempts(),
                "Refresh should be attempted (iteration $i)"
            );

            // Verify: handleFailedRefresh was called
            $this->assertTrue(
                $mockRefresher->wasHandleFailedCalled(),
                "handleFailedRefresh should be called on failed refresh (iteration $i)"
            );

            // Verify: tokens are cleared
            $this->assertNull(
                TokenHandler::getAccessToken(),
                "Access token should be cleared after failed refresh (iteration $i)"
            );

            $this->assertNull(
                TokenHandler::getRefreshToken(),
                "Refresh token should be cleared after failed refresh (iteration $i)"
            );
        }
    }

    /**
     * Property 3: For any non-401 response, no refresh should be attempted.
     *
     * **Validates: Requirements 4.1**
     */
    #[Test]
    public function noRefreshOnNon401Responses(): void
    {
        $nonRefreshStatusCodes = [200, 201, 204, 400, 403, 404, 500, 502, 503];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $_SESSION = [];

            // Generate random tokens
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();

            // Store tokens
            TokenHandler::setTokens($accessToken, $refreshToken);

            // Pick a random non-401 status code
            $statusCode = $nonRefreshStatusCodes[array_rand($nonRefreshStatusCodes)];

            // Create mock
            $mockRefresher = new MockTokenRefresher(true);

            // Simulate non-401 response
            $forwarder = new TestableRequestForwarder($mockRefresher, [
                ['status' => $statusCode, 'body' => null]
            ]);

            // Execute request
            $response = $forwarder->forward('GET', '/api/test');

            // Verify: no refresh attempt
            $this->assertSame(
                0,
                $mockRefresher->getRefreshAttempts(),
                "No refresh should be attempted for status $statusCode (iteration $i)"
            );

            // Verify: original tokens are preserved
            $this->assertSame(
                $accessToken,
                TokenHandler::getAccessToken(),
                "Access token should be preserved for status $statusCode (iteration $i)"
            );

            $this->assertSame(
                $refreshToken,
                TokenHandler::getRefreshToken(),
                "Refresh token should be preserved for status $statusCode (iteration $i)"
            );
        }
    }

    /**
     * Property 3: For any 401 response with withAuth=false,
     * no refresh should be attempted.
     *
     * **Validates: Requirements 4.1**
     */
    #[Test]
    public function noRefreshWhenAuthDisabled(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $_SESSION = [];

            // Generate random tokens
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();

            // Store tokens
            TokenHandler::setTokens($accessToken, $refreshToken);

            // Create mock
            $mockRefresher = new MockTokenRefresher(true);

            // Simulate 401 response
            $forwarder = new TestableRequestForwarder($mockRefresher, [
                ['status' => 401, 'body' => null]
            ]);

            // Execute request with withAuth=false
            $response = $forwarder->forward('GET', '/api/test', [], [], false);

            // Verify: no refresh attempt
            $this->assertSame(
                0,
                $mockRefresher->getRefreshAttempts(),
                "No refresh should be attempted when auth is disabled (iteration $i)"
            );

            // Verify: tokens are preserved
            $this->assertSame(
                $accessToken,
                TokenHandler::getAccessToken(),
                "Access token should be preserved when auth is disabled (iteration $i)"
            );
        }
    }

    /**
     * Property 3: For any HTTP method (GET, POST, PUT, DELETE, PATCH),
     * the refresh behavior should be consistent.
     *
     * **Validates: Requirements 4.1, 4.2**
     */
    #[Test]
    public function refreshBehaviorConsistentAcrossHttpMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $method = $methods[array_rand($methods)];
            $_SESSION = [];

            // Generate random tokens
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();
            $newAccessToken = $this->generateRandomJwtToken();

            // Store tokens
            TokenHandler::setTokens($accessToken, $refreshToken);

            // Create mock
            $mockRefresher = new MockTokenRefresher(true, $newAccessToken);

            // Simulate 401 followed by 200
            $forwarder = new TestableRequestForwarder($mockRefresher, [
                ['status' => 401, 'body' => null],
                ['status' => 200, 'body' => null]
            ]);

            // Execute request with random method
            $response = $forwarder->forward($method, '/api/test', ['data' => 'test']);

            // Verify: refresh was attempted
            $this->assertSame(
                1,
                $mockRefresher->getRefreshAttempts(),
                "Refresh should be attempted for $method (iteration $i)"
            );

            // Verify: request was retried
            $this->assertSame(
                2,
                $forwarder->getRequestCount(),
                "Request should be retried for $method (iteration $i)"
            );

            // Verify: new token is stored
            $this->assertSame(
                $newAccessToken,
                TokenHandler::getAccessToken(),
                "New access token should be stored for $method (iteration $i)"
            );
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
}
