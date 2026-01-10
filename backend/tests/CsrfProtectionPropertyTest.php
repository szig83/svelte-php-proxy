<?php
/**
 * Property-Based Test: CSRF Token Validáció
 *
 * **Tulajdonság 8: CSRF Token Validáció**
 * *Bármely* állapotváltoztató kérés (POST, PUT, DELETE, PATCH) esetén,
 * a PHP_Proxy-nak ellenőriznie kell a CSRF tokent, és érvénytelen token
 * esetén el kell utasítania a kérést.
 *
 * **Validálja: Követelmények 8.5**
 *
 * Feature: svelte-php-proxy-auth, Property 8: CSRF Token Validáció
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\CsrfProtection;
use App\Session;

class CsrfProtectionPropertyTest extends TestCase
{
    private const STATE_CHANGING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const NON_STATE_CHANGING_METHODS = ['GET', 'HEAD', 'OPTIONS'];
    private const ITERATIONS = 100;

    protected function setUp(): void
    {
        // Reset session state before each test
        $_SESSION = [];
        $_SERVER = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        $_POST = [];
    }

    /**
     * Property 8: For any state-changing request with a valid CSRF token,
     * the validation should pass.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function validCsrfTokenAlwaysPassesValidation(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];

            // Generate a token and store it in session
            $token = CsrfProtection::generateToken();

            // Validate the same token - should always pass
            $result = CsrfProtection::validateToken($token);

            $this->assertTrue(
                $result,
                "Valid CSRF token should always pass validation (iteration $i)"
            );
        }
    }

    /**
     * Property 8: For any state-changing request with an invalid CSRF token,
     * the validation should fail.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function invalidCsrfTokenAlwaysFailsValidation(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];

            // Generate a valid token
            CsrfProtection::generateToken();

            // Generate a random invalid token
            $invalidToken = $this->generateRandomToken();

            // Validate the invalid token - should always fail
            $result = CsrfProtection::validateToken($invalidToken);

            $this->assertFalse(
                $result,
                "Invalid CSRF token should always fail validation (iteration $i)"
            );
        }
    }

    /**
     * Property 8: Empty tokens should always fail validation.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function emptyTokenAlwaysFailsValidation(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];

            // Generate a valid token in session
            CsrfProtection::generateToken();

            // Empty token should fail
            $result = CsrfProtection::validateToken('');

            $this->assertFalse(
                $result,
                "Empty CSRF token should always fail validation (iteration $i)"
            );
        }
    }

    /**
     * Property 8: Validation should fail when no token exists in session.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function validationFailsWithNoSessionToken(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state - no token in session
            $_SESSION = [];

            // Generate a random token to validate
            $token = $this->generateRandomToken();

            // Should fail because no token in session
            $result = CsrfProtection::validateToken($token);

            $this->assertFalse(
                $result,
                "Validation should fail when no token exists in session (iteration $i)"
            );
        }
    }

    /**
     * Property 8: State-changing methods should be correctly identified.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function stateChangingMethodsAreCorrectlyIdentified(): void
    {
        foreach (self::STATE_CHANGING_METHODS as $method) {
            $_SERVER['REQUEST_METHOD'] = $method;

            $this->assertTrue(
                CsrfProtection::isStateChangingRequest(),
                "Method $method should be identified as state-changing"
            );
        }
    }

    /**
     * Property 8: Non-state-changing methods should not require CSRF validation.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function nonStateChangingMethodsAreCorrectlyIdentified(): void
    {
        foreach (self::NON_STATE_CHANGING_METHODS as $method) {
            $_SERVER['REQUEST_METHOD'] = $method;

            $this->assertFalse(
                CsrfProtection::isStateChangingRequest(),
                "Method $method should not be identified as state-changing"
            );
        }
    }

    /**
     * Property 8: For any state-changing method, protect() should fail
     * without a valid token.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function protectFailsForStateChangingRequestsWithoutToken(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];
            $_SERVER = [];
            $_POST = [];

            // Pick a random state-changing method
            $method = self::STATE_CHANGING_METHODS[array_rand(self::STATE_CHANGING_METHODS)];
            $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['REQUEST_URI'] = '/api/some-endpoint';

            // Generate a token in session
            CsrfProtection::generateToken();

            // No token in request - should fail
            $result = CsrfProtection::protect();

            $this->assertFalse(
                $result,
                "protect() should fail for $method request without token (iteration $i)"
            );
        }
    }

    /**
     * Property 8: For any state-changing method, protect() should pass
     * with a valid token in header.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function protectPassesForStateChangingRequestsWithValidHeaderToken(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];
            $_SERVER = [];
            $_POST = [];

            // Pick a random state-changing method
            $method = self::STATE_CHANGING_METHODS[array_rand(self::STATE_CHANGING_METHODS)];
            $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['REQUEST_URI'] = '/api/some-endpoint';

            // Generate a token
            $token = CsrfProtection::generateToken();

            // Set token in header
            $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

            // Should pass
            $result = CsrfProtection::protect();

            $this->assertTrue(
                $result,
                "protect() should pass for $method request with valid header token (iteration $i)"
            );
        }
    }

    /**
     * Property 8: For any state-changing method, protect() should pass
     * with a valid token in POST body.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function protectPassesForStateChangingRequestsWithValidPostToken(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];
            $_SERVER = [];
            $_POST = [];

            // Pick a random state-changing method
            $method = self::STATE_CHANGING_METHODS[array_rand(self::STATE_CHANGING_METHODS)];
            $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['REQUEST_URI'] = '/api/some-endpoint';

            // Generate a token
            $token = CsrfProtection::generateToken();

            // Set token in POST body
            $_POST['_csrf_token'] = $token;

            // Should pass
            $result = CsrfProtection::protect();

            $this->assertTrue(
                $result,
                "protect() should pass for $method request with valid POST token (iteration $i)"
            );
        }
    }

    /**
     * Property 8: Non-state-changing requests should always pass protect().
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function protectAlwaysPassesForNonStateChangingRequests(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];
            $_SERVER = [];
            $_POST = [];

            // Pick a random non-state-changing method
            $method = self::NON_STATE_CHANGING_METHODS[array_rand(self::NON_STATE_CHANGING_METHODS)];
            $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['REQUEST_URI'] = '/api/some-endpoint';

            // No token needed - should pass
            $result = CsrfProtection::protect();

            $this->assertTrue(
                $result,
                "protect() should always pass for $method request (iteration $i)"
            );
        }
    }

    /**
     * Property 8: Token regeneration should invalidate old tokens.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function tokenRegenerationInvalidatesOldTokens(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];

            // Generate initial token
            $oldToken = CsrfProtection::generateToken();

            // Regenerate token
            $newToken = CsrfProtection::regenerateToken();

            // Old token should be invalid
            $oldResult = CsrfProtection::validateToken($oldToken);

            // New token should be valid
            $newResult = CsrfProtection::validateToken($newToken);

            $this->assertFalse(
                $oldResult,
                "Old token should be invalid after regeneration (iteration $i)"
            );

            $this->assertTrue(
                $newResult,
                "New token should be valid after regeneration (iteration $i)"
            );
        }
    }

    /**
     * Property 8: Excluded paths should bypass CSRF protection.
     *
     * **Validates: Requirements 8.5**
     */
    #[Test]
    public function excludedPathsBypassCsrfProtection(): void
    {
        $excludedPaths = ['/auth/login', '/api/public'];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];
            $_SERVER = [];
            $_POST = [];

            // Pick a random state-changing method
            $method = self::STATE_CHANGING_METHODS[array_rand(self::STATE_CHANGING_METHODS)];
            $_SERVER['REQUEST_METHOD'] = $method;

            // Pick a random excluded path
            $path = $excludedPaths[array_rand($excludedPaths)];
            $_SERVER['REQUEST_URI'] = $path;

            // No token - but should pass because path is excluded
            $result = CsrfProtection::protect($excludedPaths);

            $this->assertTrue(
                $result,
                "protect() should pass for excluded path $path (iteration $i)"
            );
        }
    }

    /**
     * Generate a random token for testing
     */
    private function generateRandomToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
