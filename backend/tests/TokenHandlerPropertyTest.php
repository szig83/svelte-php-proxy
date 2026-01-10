<?php
/**
 * Property-Based Test: Session Token Tárolás Körforgás
 *
 * **Tulajdonság 2: Session Token Tárolás Körforgás**
 * *Bármely* sikeres bejelentkezés esetén, ha a Külső_API tokeneket ad vissza,
 * majd a PHP_Proxy tárolja őket a session-ben, akkor a session-ből visszaolvasott
 * tokeneknek meg kell egyezniük az eredetileg tárolt tokenekkel.
 *
 * **Validálja: Követelmények 3.2**
 *
 * Feature: svelte-php-proxy-auth, Property 2: Session Token Tárolás Körforgás
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\TokenHandler;
use App\Session;

class TokenHandlerPropertyTest extends TestCase
{
    private const ITERATIONS = 100;

    protected function setUp(): void
    {
        // Reset session state before each test
        $_SESSION = [];
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_SERVER = [];
    }

    /**
     * Property 2: For any valid access token and refresh token pair,
     * storing them in session and reading them back should return
     * the exact same values.
     *
     * **Validates: Requirements 3.2**
     */
    #[Test]
    public function tokenStorageRoundTripPreservesTokens(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];

            // Generate random tokens (simulating External API response)
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();

            // Store tokens in session (simulating PHP_Proxy storing tokens)
            TokenHandler::setTokens($accessToken, $refreshToken);

            // Read tokens back from session
            $retrievedAccessToken = TokenHandler::getAccessToken();
            $retrievedRefreshToken = TokenHandler::getRefreshToken();

            // Verify round-trip: stored tokens must equal retrieved tokens
            $this->assertSame(
                $accessToken,
                $retrievedAccessToken,
                "Access token round-trip failed (iteration $i): stored '$accessToken' but retrieved '$retrievedAccessToken'"
            );

            $this->assertSame(
                $refreshToken,
                $retrievedRefreshToken,
                "Refresh token round-trip failed (iteration $i): stored '$refreshToken' but retrieved '$retrievedRefreshToken'"
            );
        }
    }

    /**
     * Property 2: For any valid token with expiration time,
     * storing and reading back should preserve the expiration.
     *
     * **Validates: Requirements 3.2**
     */
    #[Test]
    public function tokenStorageRoundTripPreservesExpiration(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];

            // Generate random tokens and expiration
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();
            $expiresIn = random_int(300, 86400); // 5 minutes to 24 hours

            // Store tokens with expiration
            $beforeStore = time();
            TokenHandler::setTokens($accessToken, $refreshToken, $expiresIn);
            $afterStore = time();

            // Read expiration back
            $retrievedExpiresAt = TokenHandler::getTokenExpiresAt();

            // Verify expiration is within expected range
            $this->assertNotNull(
                $retrievedExpiresAt,
                "Token expiration should not be null (iteration $i)"
            );

            $expectedMinExpires = $beforeStore + $expiresIn;
            $expectedMaxExpires = $afterStore + $expiresIn;

            $this->assertGreaterThanOrEqual(
                $expectedMinExpires,
                $retrievedExpiresAt,
                "Token expiration should be at least $expectedMinExpires (iteration $i)"
            );

            $this->assertLessThanOrEqual(
                $expectedMaxExpires,
                $retrievedExpiresAt,
                "Token expiration should be at most $expectedMaxExpires (iteration $i)"
            );
        }
    }

    /**
     * Property 2: For any valid user data,
     * storing and reading back should preserve all user fields.
     *
     * **Validates: Requirements 3.2**
     */
    #[Test]
    public function userDataStorageRoundTripPreservesAllFields(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];

            // Generate random user data (simulating External API response)
            $user = $this->generateRandomUser();

            // Store user data
            TokenHandler::setUser($user);

            // Read user data back
            $retrievedUser = TokenHandler::getUser();

            // Verify round-trip: all fields must match
            $this->assertSame(
                $user['id'],
                $retrievedUser['id'],
                "User ID round-trip failed (iteration $i)"
            );

            $this->assertSame(
                $user['email'],
                $retrievedUser['email'],
                "User email round-trip failed (iteration $i)"
            );

            $this->assertSame(
                $user['name'],
                $retrievedUser['name'],
                "User name round-trip failed (iteration $i)"
            );

            $this->assertSame(
                $user['permissions'],
                $retrievedUser['permissions'],
                "User permissions round-trip failed (iteration $i)"
            );
        }
    }

    /**
     * Property 2: For any sequence of token updates,
     * the latest tokens should always be retrievable.
     *
     * **Validates: Requirements 3.2**
     */
    #[Test]
    public function tokenUpdateRoundTripPreservesLatestTokens(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];

            // Initial tokens
            $initialAccessToken = $this->generateRandomJwtToken();
            $initialRefreshToken = $this->generateRandomJwtToken();
            TokenHandler::setTokens($initialAccessToken, $initialRefreshToken);

            // Update with new access token (simulating token refresh)
            $newAccessToken = $this->generateRandomJwtToken();
            $newExpiresIn = random_int(300, 3600);
            TokenHandler::updateTokens($newAccessToken, null, $newExpiresIn);

            // Verify: new access token should be returned
            $retrievedAccessToken = TokenHandler::getAccessToken();
            $this->assertSame(
                $newAccessToken,
                $retrievedAccessToken,
                "Updated access token round-trip failed (iteration $i)"
            );

            // Verify: original refresh token should still be there
            $retrievedRefreshToken = TokenHandler::getRefreshToken();
            $this->assertSame(
                $initialRefreshToken,
                $retrievedRefreshToken,
                "Original refresh token should be preserved after access token update (iteration $i)"
            );
        }
    }

    /**
     * Property 2: Clearing tokens should result in null values.
     *
     * **Validates: Requirements 3.2**
     */
    #[Test]
    public function clearTokensRemovesAllTokenData(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];

            // Store random tokens and user
            $accessToken = $this->generateRandomJwtToken();
            $refreshToken = $this->generateRandomJwtToken();
            $user = $this->generateRandomUser();

            TokenHandler::setTokens($accessToken, $refreshToken, 3600);
            TokenHandler::setUser($user);

            // Verify tokens are stored
            $this->assertNotNull(TokenHandler::getAccessToken());
            $this->assertNotNull(TokenHandler::getRefreshToken());
            $this->assertNotNull(TokenHandler::getUser());

            // Clear tokens
            TokenHandler::clearTokens();

            // Verify all token data is cleared
            $this->assertNull(
                TokenHandler::getAccessToken(),
                "Access token should be null after clear (iteration $i)"
            );

            $this->assertNull(
                TokenHandler::getRefreshToken(),
                "Refresh token should be null after clear (iteration $i)"
            );

            $this->assertNull(
                TokenHandler::getTokenExpiresAt(),
                "Token expiration should be null after clear (iteration $i)"
            );

            $this->assertNull(
                TokenHandler::getUser(),
                "User data should be null after clear (iteration $i)"
            );
        }
    }

    /**
     * Property 2: Token storage should handle special characters correctly.
     *
     * **Validates: Requirements 3.2**
     */
    #[Test]
    public function tokenStorageHandlesSpecialCharacters(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Reset state
            $_SESSION = [];

            // Generate tokens with various special characters
            $accessToken = $this->generateTokenWithSpecialChars();
            $refreshToken = $this->generateTokenWithSpecialChars();

            // Store tokens
            TokenHandler::setTokens($accessToken, $refreshToken);

            // Read tokens back
            $retrievedAccessToken = TokenHandler::getAccessToken();
            $retrievedRefreshToken = TokenHandler::getRefreshToken();

            // Verify exact match including special characters
            $this->assertSame(
                $accessToken,
                $retrievedAccessToken,
                "Access token with special chars round-trip failed (iteration $i)"
            );

            $this->assertSame(
                $refreshToken,
                $retrievedRefreshToken,
                "Refresh token with special chars round-trip failed (iteration $i)"
            );
        }
    }

    /**
     * Generate a random JWT-like token for testing
     */
    private function generateRandomJwtToken(): string
    {
        // Generate a realistic JWT structure: header.payload.signature
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
     * Generate a token with special characters
     */
    private function generateTokenWithSpecialChars(): string
    {
        $base = $this->generateRandomJwtToken();
        // JWT tokens use base64url encoding which includes +, /, =
        // Add some edge cases
        $specialChars = ['+', '/', '=', '-', '_'];
        $char = $specialChars[array_rand($specialChars)];
        return $base . $char . bin2hex(random_bytes(4));
    }

    /**
     * Generate random user data for testing
     */
    private function generateRandomUser(): array
    {
        $permissions = ['read', 'write', 'delete', 'admin', 'users:manage', 'stats:view'];
        $numPermissions = random_int(1, count($permissions));
        $userPermissions = array_slice(
            $permissions,
            0,
            $numPermissions
        );

        return [
            'id' => bin2hex(random_bytes(8)),
            'email' => $this->generateRandomEmail(),
            'name' => $this->generateRandomName(),
            'permissions' => $userPermissions
        ];
    }

    /**
     * Generate a random email address
     */
    private function generateRandomEmail(): string
    {
        $localPart = bin2hex(random_bytes(4));
        $domains = ['example.com', 'test.org', 'demo.net'];
        $domain = $domains[array_rand($domains)];
        return "$localPart@$domain";
    }

    /**
     * Generate a random name
     */
    private function generateRandomName(): string
    {
        $firstNames = ['John', 'Jane', 'Bob', 'Alice', 'Charlie', 'Diana'];
        $lastNames = ['Smith', 'Doe', 'Johnson', 'Williams', 'Brown', 'Jones'];
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
}
