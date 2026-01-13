<?php
/**
 * Property-Based Test: Backend Validáció Helyessége
 *
 * **Property 6: Backend Validáció Helyessége**
 * *Bármely* a backendre küldött hiba adat esetén, HA az adat hiányzó kötelező mezőket
 * tartalmaz (type, message, context), AKKOR a backendnek validációs hiba választ KELL visszaadnia.
 *
 * **Validálja: Követelmények 5.1**
 *
 * Feature: frontend-error-logging, Property 6: Backend Validáció Helyessége
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\ErrorLogger;

class ErrorLoggerValidationPropertyTest extends TestCase
{
    private const ITERATIONS = 100;
    private string $testStorageFile;

    protected function setUp(): void
    {
        $this->testStorageFile = sys_get_temp_dir() . '/test_errors_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testStorageFile)) {
            unlink($this->testStorageFile);
        }
    }

    /**
     * Property 6: For any error data missing the 'type' field,
     * the ErrorLogger should throw an InvalidArgumentException.
     *
     * **Validates: Requirements 5.1**
     */
    #[Test]
    public function missingTypeFieldThrowsValidationError(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate valid data but remove 'type' field
            $errorData = $this->generateValidErrorData();
            unset($errorData['type']);

            try {
                $logger->log($errorData);
                $this->fail("Expected InvalidArgumentException for missing 'type' field (iteration $i)");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'type',
                    strtolower($e->getMessage()),
                    "Exception message should mention 'type' field (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 6: For any error data missing the 'message' field,
     * the ErrorLogger should throw an InvalidArgumentException.
     *
     * **Validates: Requirements 5.1**
     */
    #[Test]
    public function missingMessageFieldThrowsValidationError(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate valid data but remove 'message' field
            $errorData = $this->generateValidErrorData();
            unset($errorData['message']);

            try {
                $logger->log($errorData);
                $this->fail("Expected InvalidArgumentException for missing 'message' field (iteration $i)");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'message',
                    strtolower($e->getMessage()),
                    "Exception message should mention 'message' field (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 6: For any error data missing the 'context' field,
     * the ErrorLogger should throw an InvalidArgumentException.
     *
     * **Validates: Requirements 5.1**
     */
    #[Test]
    public function missingContextFieldThrowsValidationError(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate valid data but remove 'context' field
            $errorData = $this->generateValidErrorData();
            unset($errorData['context']);

            try {
                $logger->log($errorData);
                $this->fail("Expected InvalidArgumentException for missing 'context' field (iteration $i)");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'context',
                    strtolower($e->getMessage()),
                    "Exception message should mention 'context' field (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 6: For any error data with empty string 'type' field,
     * the ErrorLogger should throw an InvalidArgumentException.
     *
     * **Validates: Requirements 5.1**
     */
    #[Test]
    public function emptyTypeFieldThrowsValidationError(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate valid data but set 'type' to empty string
            $errorData = $this->generateValidErrorData();
            $errorData['type'] = '';

            try {
                $logger->log($errorData);
                $this->fail("Expected InvalidArgumentException for empty 'type' field (iteration $i)");
            } catch (\InvalidArgumentException $e) {
                // Validation should fail for empty type
                $this->assertTrue(true);
            }
        }
    }

    /**
     * Property 6: For any error data with empty string 'message' field,
     * the ErrorLogger should throw an InvalidArgumentException.
     *
     * **Validates: Requirements 5.1**
     */
    #[Test]
    public function emptyMessageFieldThrowsValidationError(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate valid data but set 'message' to empty string
            $errorData = $this->generateValidErrorData();
            $errorData['message'] = '';

            try {
                $logger->log($errorData);
                $this->fail("Expected InvalidArgumentException for empty 'message' field (iteration $i)");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'message',
                    strtolower($e->getMessage()),
                    "Exception message should mention 'message' field (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 6: For any error data with invalid 'type' value,
     * the ErrorLogger should throw an InvalidArgumentException.
     *
     * **Validates: Requirements 5.1**
     */
    #[Test]
    public function invalidTypeValueThrowsValidationError(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate valid data but set 'type' to invalid value
            $errorData = $this->generateValidErrorData();
            $errorData['type'] = $this->generateRandomInvalidType();

            try {
                $logger->log($errorData);
                $this->fail("Expected InvalidArgumentException for invalid 'type' value (iteration $i)");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'type',
                    strtolower($e->getMessage()),
                    "Exception message should mention 'type' field (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 6: For any error data with context missing required 'url' field,
     * the ErrorLogger should throw an InvalidArgumentException.
     *
     * **Validates: Requirements 5.1**
     */
    #[Test]
    public function missingContextUrlThrowsValidationError(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate valid data but remove 'url' from context
            $errorData = $this->generateValidErrorData();
            unset($errorData['context']['url']);

            try {
                $logger->log($errorData);
                $this->fail("Expected InvalidArgumentException for missing context 'url' field (iteration $i)");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'url',
                    strtolower($e->getMessage()),
                    "Exception message should mention 'url' field (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 6: For any error data with context missing required 'userAgent' field,
     * the ErrorLogger should throw an InvalidArgumentException.
     *
     * **Validates: Requirements 5.1**
     */
    #[Test]
    public function missingContextUserAgentThrowsValidationError(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate valid data but remove 'userAgent' from context
            $errorData = $this->generateValidErrorData();
            unset($errorData['context']['userAgent']);

            try {
                $logger->log($errorData);
                $this->fail("Expected InvalidArgumentException for missing context 'userAgent' field (iteration $i)");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'useragent',
                    strtolower($e->getMessage()),
                    "Exception message should mention 'userAgent' field (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 6: For any valid error data, the ErrorLogger should
     * successfully log and return an ID.
     *
     * **Validates: Requirements 5.1**
     */
    #[Test]
    public function validDataIsAccepted(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate valid data
            $errorData = $this->generateValidErrorData();

            // Should not throw exception
            $id = $logger->log($errorData);

            $this->assertNotEmpty(
                $id,
                "Valid error data should return a non-empty ID (iteration $i)"
            );

            $this->assertStringStartsWith(
                'err_',
                $id,
                "Error ID should start with 'err_' prefix (iteration $i)"
            );
        }
    }

    /**
     * Generate valid error data for testing
     */
    private function generateValidErrorData(): array
    {
        $types = ['javascript', 'api', 'manual'];
        $severities = ['error', 'warning', 'info'];

        return [
            'type' => $types[array_rand($types)],
            'severity' => $severities[array_rand($severities)],
            'message' => $this->generateRandomMessage(),
            'stack' => $this->generateRandomStackTrace(),
            'context' => [
                'url' => $this->generateRandomUrl(),
                'userAgent' => $this->generateRandomUserAgent(),
                'userId' => bin2hex(random_bytes(8)),
                'appVersion' => '1.' . random_int(0, 9) . '.' . random_int(0, 99),
            ],
            'timestamp' => date('c'),
        ];
    }

    /**
     * Generate a random error message
     */
    private function generateRandomMessage(): string
    {
        $messages = [
            'Uncaught TypeError: Cannot read property',
            'NetworkError: Failed to fetch',
            'ReferenceError: undefined is not defined',
            'SyntaxError: Unexpected token',
            'RangeError: Maximum call stack size exceeded',
        ];
        return $messages[array_rand($messages)] . ' ' . bin2hex(random_bytes(4));
    }

    /**
     * Generate a random stack trace
     */
    private function generateRandomStackTrace(): string
    {
        $lines = random_int(3, 10);
        $stack = [];
        for ($i = 0; $i < $lines; $i++) {
            $file = 'file' . random_int(1, 100) . '.js';
            $line = random_int(1, 1000);
            $col = random_int(1, 100);
            $stack[] = "    at function$i ($file:$line:$col)";
        }
        return implode("\n", $stack);
    }

    /**
     * Generate a random URL
     */
    private function generateRandomUrl(): string
    {
        $paths = ['/home', '/dashboard', '/settings', '/profile', '/admin'];
        return 'https://example.com' . $paths[array_rand($paths)] . '/' . bin2hex(random_bytes(4));
    }

    /**
     * Generate a random user agent string
     */
    private function generateRandomUserAgent(): string
    {
        $browsers = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Safari/17.0',
            'Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0',
        ];
        return $browsers[array_rand($browsers)];
    }

    /**
     * Generate a random invalid type value
     */
    private function generateRandomInvalidType(): string
    {
        $invalidTypes = [
            'invalid',
            'error',
            'warning',
            'info',
            'debug',
            'critical',
            'unknown',
            bin2hex(random_bytes(4)),
        ];
        return $invalidTypes[array_rand($invalidTypes)];
    }
}
