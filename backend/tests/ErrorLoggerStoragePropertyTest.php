<?php
/**
 * Property-Based Test: Backend Tárolás Round-Trip
 *
 * **Property 7: Backend Tárolás Round-Trip**
 * *Bármely* érvényes hiba bejegyzés esetén, amely a backend-en keresztül tárolásra kerül,
 * a visszakapott ID alapján történő lekérdezésnek egy ekvivalens hiba bejegyzést KELL
 * visszaadnia az összes eredeti mezővel.
 *
 * **Validálja: Követelmények 5.2, 5.3**
 *
 * Feature: frontend-error-logging, Property 7: Backend Tárolás Round-Trip
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\ErrorLogger;

class ErrorLoggerStoragePropertyTest extends TestCase
{
    private const ITERATIONS = 100;
    private string $testStorageFile;

    protected function setUp(): void
    {
        $this->testStorageFile = sys_get_temp_dir() . '/test_errors_storage_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testStorageFile)) {
            unlink($this->testStorageFile);
        }
    }

    /**
     * Property 7: For any valid error entry stored through the backend,
     * retrieving it by ID should return an equivalent entry with all original fields.
     *
     * **Validates: Requirements 5.2, 5.3**
     */
    #[Test]
    public function storedErrorCanBeRetrievedWithAllOriginalFields(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $errorData = $this->generateValidErrorData();

            // Store the error
            $id = $logger->log($errorData);

            // Retrieve the error by ID
            $retrieved = $logger->getError($id);

            // Verify the error was retrieved
            $this->assertNotNull(
                $retrieved,
                "Stored error should be retrievable by ID (iteration $i)"
            );

            // Verify all original fields are preserved
            $this->assertEquals(
                $errorData['type'],
                $retrieved['type'],
                "Type field should be preserved (iteration $i)"
            );

            $this->assertEquals(
                $errorData['severity'],
                $retrieved['severity'],
                "Severity field should be preserved (iteration $i)"
            );

            $this->assertEquals(
                $errorData['message'],
                $retrieved['message'],
                "Message field should be preserved (iteration $i)"
            );

            $this->assertEquals(
                $errorData['stack'] ?? null,
                $retrieved['stack'],
                "Stack field should be preserved (iteration $i)"
            );

            $this->assertEquals(
                $errorData['context']['url'],
                $retrieved['context']['url'],
                "Context URL should be preserved (iteration $i)"
            );

            $this->assertEquals(
                $errorData['context']['userAgent'],
                $retrieved['context']['userAgent'],
                "Context userAgent should be preserved (iteration $i)"
            );

            $this->assertEquals(
                $errorData['timestamp'],
                $retrieved['timestamp'],
                "Timestamp should be preserved (iteration $i)"
            );

            // Verify ID is assigned
            $this->assertEquals(
                $id,
                $retrieved['id'],
                "Retrieved error should have the same ID (iteration $i)"
            );

            // Verify receivedAt is added
            $this->assertArrayHasKey(
                'receivedAt',
                $retrieved,
                "Retrieved error should have receivedAt field (iteration $i)"
            );
        }
    }

    /**
     * Property 7: For any valid error with optional context fields,
     * all optional fields should be preserved after round-trip.
     *
     * **Validates: Requirements 5.2, 5.3**
     */
    #[Test]
    public function optionalContextFieldsArePreserved(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $errorData = $this->generateValidErrorDataWithOptionalFields();

            // Store the error
            $id = $logger->log($errorData);

            // Retrieve the error by ID
            $retrieved = $logger->getError($id);

            // Verify optional context fields are preserved
            if (isset($errorData['context']['userId'])) {
                $this->assertEquals(
                    $errorData['context']['userId'],
                    $retrieved['context']['userId'],
                    "Context userId should be preserved (iteration $i)"
                );
            }

            if (isset($errorData['context']['appVersion'])) {
                $this->assertEquals(
                    $errorData['context']['appVersion'],
                    $retrieved['context']['appVersion'],
                    "Context appVersion should be preserved (iteration $i)"
                );
            }

            if (isset($errorData['context']['extra'])) {
                $this->assertEquals(
                    $errorData['context']['extra'],
                    $retrieved['context']['extra'],
                    "Context extra should be preserved (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 7: Multiple errors stored should all be retrievable by their IDs.
     *
     * **Validates: Requirements 5.2, 5.3**
     */
    #[Test]
    public function multipleErrorsCanBeRetrievedIndependently(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);
        $storedErrors = [];

        // Store multiple errors
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $errorData = $this->generateValidErrorData();
            $id = $logger->log($errorData);
            $storedErrors[$id] = $errorData;
        }

        // Verify all errors can be retrieved
        foreach ($storedErrors as $id => $originalData) {
            $retrieved = $logger->getError($id);

            $this->assertNotNull(
                $retrieved,
                "Each stored error should be retrievable by its ID"
            );

            $this->assertEquals(
                $originalData['message'],
                $retrieved['message'],
                "Each retrieved error should have its original message"
            );
        }
    }

    /**
     * Property 7: Errors with special characters in message should be preserved.
     *
     * **Validates: Requirements 5.2, 5.3**
     */
    #[Test]
    public function specialCharactersInMessageArePreserved(): void
    {
        $logger = new ErrorLogger($this->testStorageFile);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $errorData = $this->generateValidErrorData();
            $errorData['message'] = $this->generateMessageWithSpecialChars();

            // Store the error
            $id = $logger->log($errorData);

            // Retrieve the error by ID
            $retrieved = $logger->getError($id);

            $this->assertEquals(
                $errorData['message'],
                $retrieved['message'],
                "Message with special characters should be preserved (iteration $i)"
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
            ],
            'timestamp' => date('c'),
        ];
    }

    /**
     * Generate valid error data with optional fields
     */
    private function generateValidErrorDataWithOptionalFields(): array
    {
        $data = $this->generateValidErrorData();

        // Randomly add optional fields
        if (random_int(0, 1) === 1) {
            $data['context']['userId'] = 'user_' . bin2hex(random_bytes(8));
        }

        if (random_int(0, 1) === 1) {
            $data['context']['appVersion'] = '1.' . random_int(0, 9) . '.' . random_int(0, 99);
        }

        if (random_int(0, 1) === 1) {
            $data['context']['extra'] = [
                'customField' => bin2hex(random_bytes(4)),
                'numericValue' => random_int(1, 1000),
            ];
        }

        return $data;
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
     * Generate a message with special characters
     */
    private function generateMessageWithSpecialChars(): string
    {
        $specialChars = [
            'Error with "quotes" and \'apostrophes\'',
            'Error with <html> tags & entities',
            'Error with unicode: áéíóú ñ ü',
            'Error with emoji: 🔥 💥 ⚠️',
            'Error with newlines:\nline1\nline2',
            'Error with tabs:\t\ttabbed',
            'Error with backslash: C:\\path\\to\\file',
            'Error with JSON: {"key": "value"}',
        ];
        return $specialChars[array_rand($specialChars)] . ' ' . bin2hex(random_bytes(4));
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
}
