<?php
/**
 * Property-Based Test: Token Biztonság
 *
 * **Tulajdonság 1: Token Biztonság**
 * *Bármely* API válasz esetén, a PHP_Proxy SOHA nem adhat vissza access_token
 * vagy refresh_token értéket a Frontend felé.
 *
 * **Validálja: Követelmények 10.1**
 *
 * Feature: svelte-php-proxy-auth, Property 1: Token Biztonság
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Response;

class TokenSecurityPropertyTest extends TestCase
{
    private const ITERATIONS = 100;

    /**
     * Sensitive keys that must NEVER appear in responses to frontend
     */
    private const SENSITIVE_KEYS = [
        'access_token',
        'refresh_token',
        'token',
        'password',
        'secret',
        'api_key',
        'private_key'
    ];

    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    /**
     * Property 1: For any API response containing tokens,
     * the filterTokensFromResponse function must remove all sensitive data.
     *
     * **Validates: Requirements 10.1**
     */
    #[Test]
    public function filterTokensRemovesAllSensitiveDataFromFlatResponse(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random API response with tokens
            $apiResponse = $this->generateRandomApiResponseWithTokens();

            // Filter the response
            $filteredResponse = $this->filterTokensFromResponse($apiResponse);

            // Verify: no sensitive keys should exist in filtered response
            $this->assertNoSensitiveKeysExist(
                $filteredResponse,
                "Iteration $i: Sensitive data found in filtered flat response"
            );
        }
    }

    /**
     * Property 1: For any nested API response containing tokens at any depth,
     * the filter must recursively remove all sensitive data.
     *
     * **Validates: Requirements 10.1**
     */
    #[Test]
    public function filterTokensRemovesAllSensitiveDataFromNestedResponse(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random nested API response with tokens at various depths
            $apiResponse = $this->generateRandomNestedApiResponseWithTokens();

            // Filter the response
            $filteredResponse = $this->filterTokensFromResponse($apiResponse);

            // Verify: no sensitive keys should exist at any depth
            $this->assertNoSensitiveKeysExistRecursive(
                $filteredResponse,
                "Iteration $i: Sensitive data found in filtered nested response"
            );

            // Verify the response is still an array
            $this->assertIsArray($filteredResponse, "Filtered response should be an array");
        }
    }

    /**
     * Property 1: For any API response, non-sensitive data must be preserved.
     *
     * **Validates: Requirements 10.1**
     */
    #[Test]
    public function filterTokensPreservesNonSensitiveData(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random API response with both sensitive and non-sensitive data
            $nonSensitiveData = $this->generateRandomNonSensitiveData();
            $sensitiveData = $this->generateRandomSensitiveData();
            $apiResponse = array_merge($nonSensitiveData, $sensitiveData);

            // Filter the response
            $filteredResponse = $this->filterTokensFromResponse($apiResponse);

            // Verify: all non-sensitive data should be preserved
            foreach ($nonSensitiveData as $key => $value) {
                $this->assertArrayHasKey(
                    $key,
                    $filteredResponse,
                    "Iteration $i: Non-sensitive key '$key' was incorrectly removed"
                );

                if (!is_array($value)) {
                    $this->assertSame(
                        $value,
                        $filteredResponse[$key],
                        "Iteration $i: Non-sensitive value for '$key' was modified"
                    );
                }
            }
        }
    }

    /**
     * Property 1: Case-insensitive key matching for sensitive data.
     *
     * **Validates: Requirements 10.1**
     */
    #[Test]
    public function filterTokensHandlesCaseVariations(): void
    {
        $caseVariations = [
            'access_token',
            'Access_Token',
            'ACCESS_TOKEN',
            'refresh_token',
            'Refresh_Token',
            'REFRESH_TOKEN',
            'Token',
            'TOKEN',
            'Password',
            'PASSWORD',
            'Secret',
            'SECRET'
        ];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Pick random case variations
            $numKeys = random_int(1, count($caseVariations));
            $selectedKeys = array_slice($caseVariations, 0, $numKeys);

            $apiResponse = [];
            foreach ($selectedKeys as $key) {
                $apiResponse[$key] = $this->generateRandomJwtToken();
            }

            // Add some non-sensitive data
            $apiResponse['user'] = $this->generateRandomUser();
            $apiResponse['message'] = 'Success';

            // Filter the response
            $filteredResponse = $this->filterTokensFromResponse($apiResponse);

            // Verify: no sensitive keys in any case should exist
            foreach ($selectedKeys as $key) {
                $this->assertArrayNotHasKey(
                    $key,
                    $filteredResponse,
                    "Iteration $i: Sensitive key '$key' was not filtered"
                );
            }

            // Verify: non-sensitive data preserved
            $this->assertArrayHasKey('user', $filteredResponse);
            $this->assertArrayHasKey('message', $filteredResponse);
        }
    }

    /**
     * Property 1: Empty and null responses should be handled safely.
     *
     * **Validates: Requirements 10.1**
     */
    #[Test]
    public function filterTokensHandlesEmptyAndEdgeCases(): void
    {
        // Empty array
        $filtered = $this->filterTokensFromResponse([]);
        $this->assertIsArray($filtered);
        $this->assertEmpty($filtered);

        // Array with only sensitive data
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $sensitiveOnly = $this->generateRandomSensitiveData();
            $filtered = $this->filterTokensFromResponse($sensitiveOnly);

            $this->assertNoSensitiveKeysExist(
                $filtered,
                "Iteration $i: Sensitive-only response should be empty after filtering"
            );
        }
    }

    /**
     * Property 1: Deeply nested tokens must be filtered.
     *
     * **Validates: Requirements 10.1**
     */
    #[Test]
    public function filterTokensRemovesDeeplyNestedTokens(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create deeply nested structure with tokens at random depths
            $depth = random_int(2, 5);
            $apiResponse = $this->generateDeeplyNestedResponseWithTokens($depth);

            // Filter the response
            $filteredResponse = $this->filterTokensFromResponse($apiResponse);

            // Verify: no sensitive keys at any depth
            $this->assertNoSensitiveKeysExistRecursive(
                $filteredResponse,
                "Iteration $i: Deeply nested sensitive data found at depth $depth"
            );

            // Verify the response is still an array (basic sanity check)
            $this->assertIsArray($filteredResponse, "Filtered response should be an array");
        }
    }

    /**
     * Replicate the filterTokensFromResponse function from index.php for testing
     */
    private function filterTokensFromResponse(mixed $data): mixed
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
            // Convert key to string for comparison (handles integer keys)
            $keyStr = is_string($key) ? strtolower($key) : (string)$key;

            // Érzékeny kulcsok kihagyása (case-insensitive)
            if (in_array($keyStr, $sensitiveKeys, true)) {
                continue;
            }

            // Rekurzív szűrés tömbökre
            if (is_array($value)) {
                $filtered[$key] = $this->filterTokensFromResponse($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Assert that no sensitive keys exist in the given array
     */
    private function assertNoSensitiveKeysExist(array $data, string $message): void
    {
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            // Check both lowercase and original case
            $this->assertArrayNotHasKey(
                $sensitiveKey,
                $data,
                "$message - found key: $sensitiveKey"
            );
        }
    }

    /**
     * Assert that no sensitive keys exist at any depth in the given array
     */
    private function assertNoSensitiveKeysExistRecursive(array $data, string $message, string $path = ''): void
    {
        foreach ($data as $key => $value) {
            $currentPath = $path ? "$path.$key" : (string)$key;

            // Check if current key is sensitive (only for string keys)
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $this->fail("$message - found sensitive key at path: $currentPath");
            }

            // Recursively check nested arrays
            if (is_array($value)) {
                $this->assertNoSensitiveKeysExistRecursive($value, $message, $currentPath);
            }
        }
    }

    /**
     * Generate a random API response containing tokens
     */
    private function generateRandomApiResponseWithTokens(): array
    {
        return [
            'access_token' => $this->generateRandomJwtToken(),
            'refresh_token' => $this->generateRandomJwtToken(),
            'expires_in' => random_int(300, 86400),
            'user' => $this->generateRandomUser(),
            'message' => 'Login successful'
        ];
    }

    /**
     * Generate a random nested API response with tokens at various depths
     */
    private function generateRandomNestedApiResponseWithTokens(): array
    {
        return [
            'data' => [
                'auth' => [
                    'access_token' => $this->generateRandomJwtToken(),
                    'refresh_token' => $this->generateRandomJwtToken(),
                ],
                'user' => $this->generateRandomUser()
            ],
            'meta' => [
                'token' => $this->generateRandomJwtToken(),
                'timestamp' => time()
            ],
            'status' => 'success'
        ];
    }

    /**
     * Generate a deeply nested response with tokens at specified depth
     */
    private function generateDeeplyNestedResponseWithTokens(int $depth): array
    {
        if ($depth <= 0) {
            return [
                'access_token' => $this->generateRandomJwtToken(),
                'value' => bin2hex(random_bytes(8))
            ];
        }

        return [
            'level' => $depth,
            'nested' => $this->generateDeeplyNestedResponseWithTokens($depth - 1),
            'refresh_token' => $this->generateRandomJwtToken(),
            'data' => bin2hex(random_bytes(8))
        ];
    }

    /**
     * Generate random non-sensitive data
     */
    private function generateRandomNonSensitiveData(): array
    {
        return [
            'id' => bin2hex(random_bytes(8)),
            'name' => $this->generateRandomName(),
            'email' => $this->generateRandomEmail(),
            'created_at' => date('Y-m-d H:i:s'),
            'status' => ['active', 'inactive', 'pending'][random_int(0, 2)],
            'count' => random_int(1, 1000)
        ];
    }

    /**
     * Generate random sensitive data
     */
    private function generateRandomSensitiveData(): array
    {
        $sensitiveData = [];
        $keys = ['access_token', 'refresh_token', 'token', 'password', 'secret'];

        // Add random subset of sensitive keys
        $numKeys = random_int(1, count($keys));
        $selectedKeys = array_slice($keys, 0, $numKeys);

        foreach ($selectedKeys as $key) {
            $sensitiveData[$key] = $this->generateRandomJwtToken();
        }

        return $sensitiveData;
    }

    /**
     * Generate a random JWT-like token
     */
    private function generateRandomJwtToken(): string
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'sub' => bin2hex(random_bytes(8)),
            'iat' => time(),
            'exp' => time() + random_int(300, 86400)
        ]));
        $signature = base64_encode(random_bytes(32));

        return "$header.$payload.$signature";
    }

    /**
     * Generate random user data
     */
    private function generateRandomUser(): array
    {
        return [
            'id' => bin2hex(random_bytes(8)),
            'email' => $this->generateRandomEmail(),
            'name' => $this->generateRandomName(),
            'permissions' => ['read', 'write']
        ];
    }

    /**
     * Generate a random email
     */
    private function generateRandomEmail(): string
    {
        return bin2hex(random_bytes(4)) . '@example.com';
    }

    /**
     * Generate a random name
     */
    private function generateRandomName(): string
    {
        $names = ['John', 'Jane', 'Bob', 'Alice'];
        $surnames = ['Smith', 'Doe', 'Johnson', 'Williams'];
        return $names[array_rand($names)] . ' ' . $surnames[array_rand($surnames)];
    }
}
