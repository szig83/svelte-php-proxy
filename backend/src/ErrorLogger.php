<?php
/**
 * ErrorLogger osztály - Frontend hibák fogadása és tárolása
 *
 * JSON fájl alapú tárolás, validáció és lekérdezés.
 * Követelmények: 5.1, 5.2, 5.3, 5.4
 */

declare(strict_types=1);

namespace App;

class ErrorLogger
{
    private string $storageFile;
    private int $maxEntries;

    /**
     * @param string $storageFile Tárolási fájl útvonala
     * @param int $maxEntries Maximum tárolt hibák száma
     */
    public function __construct(string $storageFile, int $maxEntries = 1000)
    {
        $this->storageFile = $storageFile;
        $this->maxEntries = $maxEntries;
        $this->ensureStorageExists();
    }

    /**
     * Hiba naplózása
     * Követelmények: 5.1, 5.2, 5.3
     *
     * @param array $errorData A hiba adatok
     * @return string Az új hiba egyedi azonosítója
     * @throws \InvalidArgumentException Ha a validáció sikertelen
     */
    public function log(array $errorData): string
    {
        $this->validate($errorData);

        $entry = [
            'id' => $this->generateId(),
            'type' => $errorData['type'],
            'severity' => $errorData['severity'],
            'message' => $errorData['message'],
            'stack' => $errorData['stack'] ?? null,
            'context' => $errorData['context'],
            'timestamp' => $errorData['timestamp'],
            'receivedAt' => date('c')
        ];

        $this->store($entry);

        return $entry['id'];
    }

    /**
     * Hibák lekérdezése szűrőkkel
     * Követelmények: 6.1, 6.4, 6.5
     *
     * @param array $filters Szűrők (type, dateFrom, dateTo, page, pageSize)
     * @return array Hibák listája és metaadatok
     */
    public function getErrors(array $filters = []): array
    {
        $errors = $this->loadErrors();

        // Típus szűrő
        if (!empty($filters['type'])) {
            $errors = array_filter($errors, fn($e) => $e['type'] === $filters['type']);
        }

        // Dátum szűrők
        if (!empty($filters['dateFrom'])) {
            $dateFrom = strtotime($filters['dateFrom']);
            $errors = array_filter($errors, fn($e) => strtotime($e['timestamp']) >= $dateFrom);
        }

        if (!empty($filters['dateTo'])) {
            $dateTo = strtotime($filters['dateTo']);
            $errors = array_filter($errors, fn($e) => strtotime($e['timestamp']) <= $dateTo);
        }

        // Időrendi sorrend (legújabb elöl)
        usort($errors, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));

        $total = count($errors);

        // Lapozás
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = max(1, min(100, (int) ($filters['pageSize'] ?? 20)));
        $offset = ($page - 1) * $pageSize;

        $errors = array_slice(array_values($errors), $offset, $pageSize);

        return [
            'errors' => $errors,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }

    /**
     * Egy hiba lekérdezése ID alapján
     * Követelmények: 6.3
     *
     * @param string $id A hiba azonosítója
     * @return array|null A hiba adatok vagy null ha nem található
     */
    public function getError(string $id): ?array
    {
        $errors = $this->loadErrors();

        foreach ($errors as $error) {
            if ($error['id'] === $id) {
                return $error;
            }
        }

        return null;
    }

    /**
     * Bejövő adatok validálása
     * Követelmények: 5.1
     *
     * @param array $data A validálandó adatok
     * @throws \InvalidArgumentException Ha a validáció sikertelen
     */
    private function validate(array $data): void
    {
        $requiredFields = ['type', 'message', 'context'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw new \InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missingFields)
            );
        }

        // Type validáció
        $validTypes = ['javascript', 'api', 'manual', 'php'];
        if (!in_array($data['type'], $validTypes, true)) {
            throw new \InvalidArgumentException(
                'Invalid type. Must be one of: ' . implode(', ', $validTypes)
            );
        }

        // Severity validáció (ha megadva)
        if (isset($data['severity'])) {
            $validSeverities = ['error', 'warning', 'info'];
            if (!in_array($data['severity'], $validSeverities, true)) {
                throw new \InvalidArgumentException(
                    'Invalid severity. Must be one of: ' . implode(', ', $validSeverities)
                );
            }
        }

        // Context validáció
        if (!is_array($data['context'])) {
            throw new \InvalidArgumentException('Context must be an object');
        }

        // Context kötelező mezők
        $requiredContextFields = ['url', 'userAgent'];
        foreach ($requiredContextFields as $field) {
            if (!isset($data['context'][$field]) || trim($data['context'][$field]) === '') {
                throw new \InvalidArgumentException(
                    'Missing required context field: ' . $field
                );
            }
        }
    }

    /**
     * Hiba tárolása JSON fájlba
     *
     * @param array $entry A tárolandó hiba bejegyzés
     */
    private function store(array $entry): void
    {
        $errors = $this->loadErrors();

        // Új hiba hozzáadása az elejére
        array_unshift($errors, $entry);

        // Maximum méret betartása (FIFO)
        if (count($errors) > $this->maxEntries) {
            $errors = array_slice($errors, 0, $this->maxEntries);
        }

        $this->saveErrors($errors);
    }

    /**
     * Egyedi azonosító generálása
     *
     * @return string Egyedi azonosító
     */
    private function generateId(): string
    {
        return 'err_' . uniqid('', true);
    }

    /**
     * Tárolási fájl létezésének biztosítása
     */
    private function ensureStorageExists(): void
    {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($this->storageFile)) {
            file_put_contents($this->storageFile, '[]');
        }
    }

    /**
     * Hibák betöltése a fájlból
     *
     * @return array Hibák tömbje
     */
    private function loadErrors(): array
    {
        if (!file_exists($this->storageFile)) {
            return [];
        }

        $content = file_get_contents($this->storageFile);
        $errors = json_decode($content, true);

        return is_array($errors) ? $errors : [];
    }

    /**
     * Hibák mentése a fájlba
     *
     * @param array $errors A mentendő hibák
     */
    private function saveErrors(array $errors): void
    {
        $json = json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->storageFile, $json, LOCK_EX);
    }
}
