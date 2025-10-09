<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Support;

/**
 * Helper for debug mode error messages
 */
final class DebugHelper
{
    private static bool $debugMode = false;

    /**
     * Enable debug mode for detailed error messages
     */
    public static function enable(): void
    {
        self::$debugMode = true;
    }

    /**
     * Disable debug mode
     */
    public static function disable(): void
    {
        self::$debugMode = false;
    }

    /**
     * Check if debug mode is enabled
     */
    public static function isEnabled(): bool
    {
        return self::$debugMode;
    }

    /**
     * Format an error message with optional debug details
     *
     * @param string $message User-friendly message
     * @param array<string, mixed> $debugDetails Additional details for debug mode
     * @return string Formatted message
     */
    public static function formatError(string $message, array $debugDetails = []): string
    {
        if (!self::$debugMode || empty($debugDetails)) {
            return $message;
        }

        $details = [];
        foreach ($debugDetails as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif ($value === null) {
                $value = 'null';
            }
            $details[] = sprintf('%s: %s', $key, $value);
        }

        return sprintf('%s [Debug: %s]', $message, implode(', ', $details));
    }

    /**
     * Sanitize a file path for error messages
     * Returns relative path in production, full path in debug mode
     *
     * @param string $path Full file path
     * @param string|null $basePath Base path to make relative to
     * @return string Sanitized path
     */
    public static function sanitizePath(string $path, ?string $basePath = null): string
    {
        if (self::$debugMode) {
            return $path;
        }

        if ($basePath !== null && str_starts_with($path, $basePath)) {
            return '...' . substr($path, strlen($basePath));
        }

        // Return basename only in production
        return basename($path);
    }
}
