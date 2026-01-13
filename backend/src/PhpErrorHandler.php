<?php
/**
 * PHP Error Handler - Backend hibák naplózása
 *
 * Elkapja a PHP hibákat, kivételeket és fatal error-okat,
 * majd az ErrorLogger-rel naplózza őket.
 */

declare(strict_types=1);

namespace App;

class PhpErrorHandler
{
    private static ?ErrorLogger $errorLogger = null;
    private static bool $registered = false;

    /**
     * Error handler regisztrálása
     *
     * @param ErrorLogger $errorLogger Az ErrorLogger instance
     */
    public static function register(ErrorLogger $errorLogger): void
    {
        if (self::$registered) {
            return;
        }

        self::$errorLogger = $errorLogger;

        // PHP hibák elkapása (E_WARNING, E_NOTICE, stb.)
        set_error_handler([self::class, 'handleError']);

        // Kivételek elkapása
        set_exception_handler([self::class, 'handleException']);

        // Fatal error elkapása shutdown-kor
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$registered = true;
    }

    /**
     * PHP hibák kezelése (E_WARNING, E_NOTICE, E_DEPRECATED, stb.)
     *
     * @param int $errno Hiba szint
     * @param string $errstr Hiba üzenet
     * @param string $errfile Fájl ahol a hiba történt
     * @param int $errline Sor ahol a hiba történt
     * @return bool
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Ha a hiba el van nyomva (@), ne naplózzuk
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $severity = self::errorLevelToSeverity($errno);
        $errorType = self::errorLevelToString($errno);

        self::logPhpError(
            "[{$errorType}] {$errstr}",
            $severity,
            $errfile,
            $errline,
            null,
            $errorType
        );

        // Return false to let PHP's default error handler also run
        return false;
    }

    /**
     * Kivételek kezelése
     *
     * @param \Throwable $exception A kivétel
     */
    public static function handleException(\Throwable $exception): void
    {
        self::logPhpError(
            $exception->getMessage(),
            'error',
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString(),
            get_class($exception)
        );

        // Re-throw or handle the exception
        // In production, you might want to show a generic error page
        if (\Config::isDebugMode()) {
            throw $exception;
        }
    }

    /**
     * Shutdown handler - Fatal error-ok elkapása
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && self::isFatalError($error['type'])) {
            self::logPhpError(
                $error['message'],
                'error',
                $error['file'],
                $error['line'],
                null,
                self::errorLevelToString($error['type'])
            );
        }
    }

    /**
     * PHP hiba naplózása az ErrorLogger-rel
     */
    private static function logPhpError(
        string $message,
        string $severity,
        string $file,
        int $line,
        ?string $stack,
        string $errorType
    ): void {
        if (self::$errorLogger === null) {
            return;
        }

        try {
            $errorData = [
                'type' => 'php',
                'severity' => $severity,
                'message' => $message,
                'stack' => $stack ?? self::generateStackTrace(),
                'context' => [
                    'url' => self::getCurrentUrl(),
                    'userAgent' => 'PHP/' . PHP_VERSION,
                    'extra' => [
                        'file' => $file,
                        'line' => $line,
                        'errorType' => $errorType,
                        'phpVersion' => PHP_VERSION,
                        'serverSoftware' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                        'requestMethod' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                        'requestUri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                    ]
                ],
                'timestamp' => date('c')
            ];

            self::$errorLogger->log($errorData);
        } catch (\Throwable $e) {
            // Ha a naplózás sikertelen, ne okozzon további hibát
            error_log("PhpErrorHandler: Failed to log error: " . $e->getMessage());
        }
    }

    /**
     * Stack trace generálása
     */
    private static function generateStackTrace(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // Az első néhány elem a handler maga, azt kihagyjuk
        $trace = array_slice($trace, 3);

        $lines = [];
        foreach ($trace as $i => $frame) {
            $file = $frame['file'] ?? '[internal function]';
            $line = $frame['line'] ?? 0;
            $function = $frame['function'] ?? '';
            $class = $frame['class'] ?? '';
            $type = $frame['type'] ?? '';

            $lines[] = "#{$i} {$file}({$line}): {$class}{$type}{$function}()";
        }

        return implode("\n", $lines);
    }

    /**
     * Aktuális URL lekérése
     */
    private static function getCurrentUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return "{$protocol}://{$host}{$uri}";
    }

    /**
     * PHP hiba szint átalakítása severity-vé
     */
    private static function errorLevelToSeverity(int $level): string
    {
        return match ($level) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_PARSE => 'error',
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'warning',
            E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_STRICT => 'info',
            default => 'error'
        };
    }

    /**
     * PHP hiba szint átalakítása string-gé
     */
    private static function errorLevelToString(int $level): string
    {
        return match ($level) {
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => 'UNKNOWN'
        };
    }

    /**
     * Ellenőrzi, hogy fatal error-e
     */
    private static function isFatalError(int $type): bool
    {
        return in_array($type, [
            E_ERROR,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_PARSE
        ], true);
    }
}
