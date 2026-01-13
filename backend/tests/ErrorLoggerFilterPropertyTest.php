<?php
/**
 * Property-Based Test: Szűrés Helyessége
 *
 * **Property 8: Szűrés Helyessége**
 * *Bármely* típus szűrővel rendelkező hiba lista lekérdezés esetén az összes visszaadott
 * hibának a megadott típusúnak KELL lennie. *Bármely* dátum tartomány szűrővel rendelkező
 * lekérdezés esetén az összes visszaadott hiba időbélyegének a megadott tartományon belül
 * KELL lennie.
 *
 * **Validálja: Követelmények 6.4, 6.5**
 *
 * Feature: frontend-error-logging, Property 8: Szűrés Helyessége
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\ErrorLogger;

class ErrorLoggerFilterPropertyTest extends TestCase
{
    private const ITERATIONS = 100;
    private string $testStorageFile;

    protected function setUp(): void
    {
        $this->testStorageFile = sys_get_temp_dir() . '/test_errors_filter_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testStorageFile)) {
            unlink($this->testStorageFile);
        }
    }

    /**
     * Property 8: For any type filter, all returned errors must have the specified type.
     *
     * **Validates: Requirements 6.4**
     */
    #[Test]
    public function typeFilterReturnsOnlyMatchingTypes(): void
    {
        $types = ['javascript', 'api', 'manual'];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create fresh logger for each iteration
            $this->cleanupStorage();
            $logger = new ErrorLogger($this->testStorageFile);

            // Store errors with random types
            $errorCount = random_int(5, 20);
            for ($j = 0; $j < $errorCount; $j++) {
                $errorData = $this->generateValidErrorData();
                $errorData['type'] = $types[array_rand($types)];
                $logger->log($errorData);
            }

            // Pick a random type to filter by
            $filterType = $types[array_rand($types)];

            // Get filtered errors
            $result = $logger->getErrors(['type' => $filterType]);

            // Verify all returned errors have the specified type
            foreach ($result['errors'] as $error) {
                $this->assertEquals(
                    $filterType,
                    $error['type'],
                    "All returned errors should have type '$filterType' (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 8: For any dateFrom filter, all returned errors must have timestamps >= dateFrom.
     *
     * **Validates: Requirements 6.5**
     */
    #[Test]
    public function dateFromFilterReturnsOnlyNewerErrors(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create fresh logger for each iteration
            $this->cleanupStorage();
            $logger = new ErrorLogger($this->testStorageFile);

            // Generate a base date and store errors with various timestamps
            $baseTime = strtotime('-30 days');
            $errorCount = random_int(5, 20);

            for ($j = 0; $j < $errorCount; $j++) {
                $errorData = $this->generateValidErrorData();
                // Random timestamp within the last 60 days
                $randomOffset = random_int(0, 60) * 24 * 60 * 60;
                $errorData['timestamp'] = date('c', $baseTime + $randomOffset - (30 * 24 * 60 * 60));
                $logger->log($errorData);
            }

            // Pick a random dateFrom within the range
            $dateFromOffset = random_int(0, 30) * 24 * 60 * 60;
            $dateFrom = date('c', $baseTime - (15 * 24 * 60 * 60) + $dateFromOffset);
            $dateFromTimestamp = strtotime($dateFrom);

            // Get filtered errors
            $result = $logger->getErrors(['dateFrom' => $dateFrom]);

            // Verify all returned errors have timestamps >= dateFrom
            foreach ($result['errors'] as $error) {
                $errorTimestamp = strtotime($error['timestamp']);
                $this->assertGreaterThanOrEqual(
                    $dateFromTimestamp,
                    $errorTimestamp,
                    "All returned errors should have timestamp >= dateFrom (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 8: For any dateTo filter, all returned errors must have timestamps <= dateTo.
     *
     * **Validates: Requirements 6.5**
     */
    #[Test]
    public function dateToFilterReturnsOnlyOlderErrors(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create fresh logger for each iteration
            $this->cleanupStorage();
            $logger = new ErrorLogger($this->testStorageFile);

            // Generate errors with various timestamps
            $baseTime = time();
            $errorCount = random_int(5, 20);

            for ($j = 0; $j < $errorCount; $j++) {
                $errorData = $this->generateValidErrorData();
                // Random timestamp within the last 60 days
                $randomOffset = random_int(0, 60) * 24 * 60 * 60;
                $errorData['timestamp'] = date('c', $baseTime - $randomOffset);
                $logger->log($errorData);
            }

            // Pick a random dateTo within the range
            $dateToOffset = random_int(10, 50) * 24 * 60 * 60;
            $dateTo = date('c', $baseTime - $dateToOffset);
            $dateToTimestamp = strtotime($dateTo);

            // Get filtered errors
            $result = $logger->getErrors(['dateTo' => $dateTo]);

            // Verify all returned errors have timestamps <= dateTo
            foreach ($result['errors'] as $error) {
                $errorTimestamp = strtotime($error['timestamp']);
                $this->assertLessThanOrEqual(
                    $dateToTimestamp,
                    $errorTimestamp,
                    "All returned errors should have timestamp <= dateTo (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 8: For any date range filter (dateFrom AND dateTo), all returned errors
     * must have timestamps within the specified range.
     *
     * **Validates: Requirements 6.5**
     */
    #[Test]
    public function dateRangeFilterReturnsOnlyErrorsWithinRange(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create fresh logger for each iteration
            $this->cleanupStorage();
            $logger = new ErrorLogger($this->testStorageFile);

            // Generate errors with various timestamps
            $baseTime = time();
            $errorCount = random_int(5, 20);

            for ($j = 0; $j < $errorCount; $j++) {
                $errorData = $this->generateValidErrorData();
                // Random timestamp within the last 90 days
                $randomOffset = random_int(0, 90) * 24 * 60 * 60;
                $errorData['timestamp'] = date('c', $baseTime - $randomOffset);
                $logger->log($errorData);
            }

            // Generate a valid date range (dateFrom < dateTo)
            $fromOffset = random_int(30, 60) * 24 * 60 * 60;
            $toOffset = random_int(0, 29) * 24 * 60 * 60;

            $dateFrom = date('c', $baseTime - $fromOffset);
            $dateTo = date('c', $baseTime - $toOffset);

            $dateFromTimestamp = strtotime($dateFrom);
            $dateToTimestamp = strtotime($dateTo);

            // Get filtered errors
            $result = $logger->getErrors([
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]);

            // Verify all returned errors have timestamps within the range
            foreach ($result['errors'] as $error) {
                $errorTimestamp = strtotime($error['timestamp']);
                $this->assertGreaterThanOrEqual(
                    $dateFromTimestamp,
                    $errorTimestamp,
                    "All returned errors should have timestamp >= dateFrom (iteration $i)"
                );
                $this->assertLessThanOrEqual(
                    $dateToTimestamp,
                    $errorTimestamp,
                    "All returned errors should have timestamp <= dateTo (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 8: Combined type and date filters should return only errors matching both criteria.
     *
     * **Validates: Requirements 6.4, 6.5**
     */
    #[Test]
    public function combinedFiltersReturnOnlyMatchingErrors(): void
    {
        $types = ['javascript', 'api', 'manual'];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create fresh logger for each iteration
            $this->cleanupStorage();
            $logger = new ErrorLogger($this->testStorageFile);

            // Generate errors with various types and timestamps
            $baseTime = time();
            $errorCount = random_int(10, 30);

            for ($j = 0; $j < $errorCount; $j++) {
                $errorData = $this->generateValidErrorData();
                $errorData['type'] = $types[array_rand($types)];
                // Random timestamp within the last 60 days
                $randomOffset = random_int(0, 60) * 24 * 60 * 60;
                $errorData['timestamp'] = date('c', $baseTime - $randomOffset);
                $logger->log($errorData);
            }

            // Pick random filter criteria
            $filterType = $types[array_rand($types)];
            $fromOffset = random_int(20, 40) * 24 * 60 * 60;
            $toOffset = random_int(0, 19) * 24 * 60 * 60;

            $dateFrom = date('c', $baseTime - $fromOffset);
            $dateTo = date('c', $baseTime - $toOffset);

            $dateFromTimestamp = strtotime($dateFrom);
            $dateToTimestamp = strtotime($dateTo);

            // Get filtered errors
            $result = $logger->getErrors([
                'type' => $filterType,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]);

            // Verify all returned errors match all filter criteria
            foreach ($result['errors'] as $error) {
                $this->assertEquals(
                    $filterType,
                    $error['type'],
                    "All returned errors should have type '$filterType' (iteration $i)"
                );

                $errorTimestamp = strtotime($error['timestamp']);
                $this->assertGreaterThanOrEqual(
                    $dateFromTimestamp,
                    $errorTimestamp,
                    "All returned errors should have timestamp >= dateFrom (iteration $i)"
                );
                $this->assertLessThanOrEqual(
                    $dateToTimestamp,
                    $errorTimestamp,
                    "All returned errors should have timestamp <= dateTo (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 8: Empty filter should return all errors (no filtering applied).
     *
     * **Validates: Requirements 6.4, 6.5**
     */
    #[Test]
    public function emptyFilterReturnsAllErrors(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create fresh logger for each iteration
            $this->cleanupStorage();
            $logger = new ErrorLogger($this->testStorageFile);

            // Store a random number of errors
            $errorCount = random_int(1, 20);
            for ($j = 0; $j < $errorCount; $j++) {
                $errorData = $this->generateValidErrorData();
                $logger->log($errorData);
            }

            // Get all errors without filters
            $result = $logger->getErrors([]);

            // Verify total count matches stored count
            $this->assertEquals(
                $errorCount,
                $result['total'],
                "Empty filter should return all stored errors (iteration $i)"
            );
        }
    }

    /**
     * Clean up storage file between iterations
     */
    private function cleanupStorage(): void
    {
        if (file_exists($this->testStorageFile)) {
            unlink($this->testStorageFile);
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
}
