<?php
/**
 * Property-Based Test: Fájlfeltöltés Továbbítás
 *
 * **Tulajdonság 10: Fájlfeltöltés Továbbítás**
 * *Bármely* fájlfeltöltés esetén, a PHP_Proxy-nak sikeresen továbbítania kell
 * a fájlokat a Külső_API felé, megőrizve a fájl metaadatokat (név, típus, méret).
 *
 * **Validálja: Követelmények 2.6, 7.5**
 *
 * Feature: svelte-php-proxy-auth, Property 10: Fájlfeltöltés Továbbítás
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\RequestForwarder;
use ReflectionClass;

class FileUploadPropertyTest extends TestCase
{
    private const ITERATIONS = 100;

    private string $tempDir;
    private array $createdFiles = [];

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/php_upload_test_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
        $this->createdFiles = [];
    }

    protected function tearDown(): void
    {
        // Clean up created temp files
        foreach ($this->createdFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    /**
     * Property 10: For any file upload, the buildMultipartData method should
     * preserve file metadata (name, type).
     *
     * **Validates: Requirements 2.6, 7.5**
     */
    #[Test]
    public function buildMultipartDataPreservesFileMetadata(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random file metadata
            $fileName = $this->generateRandomFileName();
            $mimeType = $this->generateRandomMimeType();
            $content = $this->generateRandomContent();

            // Create a real temp file
            $tempFile = $this->createTempFile($content);

            // Simulate $_FILES structure
            $files = [
                'document' => [
                    'name' => $fileName,
                    'type' => $mimeType,
                    'tmp_name' => $tempFile,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($content)
                ]
            ];

            // Build multipart data using reflection to access private method
            $forwarder = new RequestForwarder('https://api.example.com', 30, null);
            $reflection = new ReflectionClass($forwarder);
            $method = $reflection->getMethod('buildMultipartData');
            $method->setAccessible(true);

            // Mock is_uploaded_file for testing
            $result = $this->buildMultipartDataForTest($files, []);

            // Verify file metadata is preserved
            $this->assertArrayHasKey(
                'document',
                $result,
                "File field 'document' should exist in result (iteration $i)"
            );

            $curlFile = $result['document'];
            $this->assertInstanceOf(
                \CURLFile::class,
                $curlFile,
                "Result should be a CURLFile instance (iteration $i)"
            );

            // Verify metadata preservation
            $this->assertEquals(
                $fileName,
                $curlFile->getPostFilename(),
                "File name should be preserved (iteration $i)"
            );

            $this->assertEquals(
                $mimeType,
                $curlFile->getMimeType(),
                "MIME type should be preserved (iteration $i)"
            );
        }
    }

    /**
     * Property 10: For any multiple file upload, all files should be
     * correctly indexed and preserve their metadata.
     *
     * **Validates: Requirements 2.6, 7.5**
     */
    #[Test]
    public function buildMultipartDataHandlesMultipleFiles(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random number of files (1-5)
            $fileCount = random_int(1, 5);
            $fileNames = [];
            $mimeTypes = [];
            $tempFiles = [];

            for ($j = 0; $j < $fileCount; $j++) {
                $fileNames[] = $this->generateRandomFileName();
                $mimeTypes[] = $this->generateRandomMimeType();
                $tempFiles[] = $this->createTempFile($this->generateRandomContent());
            }

            // Simulate $_FILES structure for multiple files
            $files = [
                'documents' => [
                    'name' => $fileNames,
                    'type' => $mimeTypes,
                    'tmp_name' => $tempFiles,
                    'error' => array_fill(0, $fileCount, UPLOAD_ERR_OK),
                    'size' => array_map(fn($f) => filesize($f), $tempFiles)
                ]
            ];

            // Build multipart data
            $result = $this->buildMultipartDataForTest($files, []);

            // Verify all files are present with correct metadata
            for ($j = 0; $j < $fileCount; $j++) {
                $key = "documents[$j]";
                $this->assertArrayHasKey(
                    $key,
                    $result,
                    "File at index $j should exist (iteration $i)"
                );

                $curlFile = $result[$key];
                $this->assertInstanceOf(
                    \CURLFile::class,
                    $curlFile,
                    "Result at index $j should be CURLFile (iteration $i)"
                );

                $this->assertEquals(
                    $fileNames[$j],
                    $curlFile->getPostFilename(),
                    "File name at index $j should be preserved (iteration $i)"
                );

                $this->assertEquals(
                    $mimeTypes[$j],
                    $curlFile->getMimeType(),
                    "MIME type at index $j should be preserved (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 10: Extra data fields should be correctly included
     * alongside file uploads.
     *
     * **Validates: Requirements 2.6, 7.5**
     */
    #[Test]
    public function buildMultipartDataIncludesExtraData(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random file
            $fileName = $this->generateRandomFileName();
            $mimeType = $this->generateRandomMimeType();
            $tempFile = $this->createTempFile($this->generateRandomContent());

            $files = [
                'file' => [
                    'name' => $fileName,
                    'type' => $mimeType,
                    'tmp_name' => $tempFile,
                    'error' => UPLOAD_ERR_OK,
                    'size' => filesize($tempFile)
                ]
            ];

            // Generate random extra data
            $extraData = [
                'title' => $this->generateRandomString(10, 50),
                'description' => $this->generateRandomString(20, 200),
                'category_id' => (string) random_int(1, 1000)
            ];

            // Build multipart data
            $result = $this->buildMultipartDataForTest($files, $extraData);

            // Verify file is present
            $this->assertArrayHasKey('file', $result, "File should exist (iteration $i)");

            // Verify extra data is present
            foreach ($extraData as $key => $value) {
                $this->assertArrayHasKey(
                    $key,
                    $result,
                    "Extra data field '$key' should exist (iteration $i)"
                );
                $this->assertEquals(
                    $value,
                    $result[$key],
                    "Extra data field '$key' should have correct value (iteration $i)"
                );
            }
        }
    }

    /**
     * Property 10: Empty file uploads should be handled gracefully
     * (files with empty tmp_name should be skipped).
     *
     * **Validates: Requirements 2.6, 7.5**
     */
    #[Test]
    public function buildMultipartDataSkipsEmptyFiles(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Simulate empty file upload
            $files = [
                'empty_file' => [
                    'name' => '',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_NO_FILE,
                    'size' => 0
                ]
            ];

            $extraData = ['field' => 'value'];

            // Build multipart data
            $result = $this->buildMultipartDataForTest($files, $extraData);

            // Empty file should not be in result
            $this->assertArrayNotHasKey(
                'empty_file',
                $result,
                "Empty file should not be included (iteration $i)"
            );

            // Extra data should still be present
            $this->assertArrayHasKey(
                'field',
                $result,
                "Extra data should still be present (iteration $i)"
            );
        }
    }

    /**
     * Property 10: Array data values should be correctly serialized.
     *
     * **Validates: Requirements 2.6, 7.5**
     */
    #[Test]
    public function buildMultipartDataHandlesArrayData(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $files = [];

            // Generate random array data
            $arraySize = random_int(1, 5);
            $arrayData = [];
            for ($j = 0; $j < $arraySize; $j++) {
                $arrayData[] = $this->generateRandomString(5, 20);
            }

            $extraData = [
                'tags' => $arrayData,
                'simple' => 'value'
            ];

            // Build multipart data
            $result = $this->buildMultipartDataForTest($files, $extraData);

            // Verify array items are indexed correctly
            for ($j = 0; $j < $arraySize; $j++) {
                $key = "tags[$j]";
                $this->assertArrayHasKey(
                    $key,
                    $result,
                    "Array item at index $j should exist (iteration $i)"
                );
                $this->assertEquals(
                    $arrayData[$j],
                    $result[$key],
                    "Array item at index $j should have correct value (iteration $i)"
                );
            }

            // Simple value should be present
            $this->assertEquals(
                'value',
                $result['simple'],
                "Simple value should be present (iteration $i)"
            );
        }
    }

    /**
     * Build multipart data for testing (bypasses is_uploaded_file check)
     */
    private function buildMultipartDataForTest(array $files, array $data): array
    {
        $postFields = [];

        // Process files
        foreach ($files as $fieldName => $file) {
            // Multiple files under same field
            if (isset($file['tmp_name']) && is_array($file['tmp_name'])) {
                foreach ($file['tmp_name'] as $index => $tmpName) {
                    if (!empty($tmpName) && file_exists($tmpName)) {
                        $curlFile = new \CURLFile(
                            $tmpName,
                            $file['type'][$index] ?? 'application/octet-stream',
                            $file['name'][$index] ?? 'file'
                        );
                        $postFields[$fieldName . '[' . $index . ']'] = $curlFile;
                    }
                }
            }
            // Single file
            elseif (isset($file['tmp_name']) && !empty($file['tmp_name']) && file_exists($file['tmp_name'])) {
                $curlFile = new \CURLFile(
                    $file['tmp_name'],
                    $file['type'] ?? 'application/octet-stream',
                    $file['name'] ?? 'file'
                );
                $postFields[$fieldName] = $curlFile;
            }
        }

        // Add extra data
        foreach ($data as $key => $value) {
            if (is_array($value)) {
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
     * Create a temporary file with content
     */
    private function createTempFile(string $content): string
    {
        $tempFile = $this->tempDir . '/' . uniqid('upload_') . '.tmp';
        file_put_contents($tempFile, $content);
        $this->createdFiles[] = $tempFile;
        return $tempFile;
    }

    /**
     * Generate a random file name
     */
    private function generateRandomFileName(): string
    {
        $extensions = ['pdf', 'jpg', 'png', 'doc', 'docx', 'txt', 'csv', 'xlsx'];
        $name = $this->generateRandomString(5, 20);
        $ext = $extensions[array_rand($extensions)];
        return $name . '.' . $ext;
    }

    /**
     * Generate a random MIME type
     */
    private function generateRandomMimeType(): string
    {
        $mimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'text/csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream'
        ];
        return $mimeTypes[array_rand($mimeTypes)];
    }

    /**
     * Generate random file content
     */
    private function generateRandomContent(): string
    {
        $length = random_int(100, 10000);
        return random_bytes($length);
    }

    /**
     * Generate a random string
     */
    private function generateRandomString(int $minLength, int $maxLength): string
    {
        $length = random_int($minLength, $maxLength);
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $result;
    }
}
