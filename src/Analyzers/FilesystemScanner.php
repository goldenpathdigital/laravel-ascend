<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Analyzers;

use SplFileInfo;

final class FilesystemScanner
{
    private ProjectContext $context;

    /**
     * @var array<int, string>|null
     */
    private ?array $allFiles = null;

    public function __construct(ProjectContext $context)
    {
        $this->context = $context;
    }

    /**
     * @return array<int, string> Absolute file paths
     */
    public function allFiles(): array
    {
        if ($this->allFiles !== null) {
            return $this->allFiles;
        }

        $root = $this->context->getRootPath();
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $relativePath = $this->toRelativePath($file->getPathname());

            if ($this->context->isExcluded($relativePath)) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        $this->allFiles = $files;

        return $files;
    }

    /**
     * @param array<int, string> $patterns
     *
     * @return array<int, string> Absolute file paths
     */
    public function findByPatterns(array $patterns): array
    {
        if ($patterns === []) {
            return [];
        }

        $regexes = array_map([$this, 'convertGlobToRegex'], $patterns);

        $matches = [];

        foreach ($this->allFiles() as $path) {
            $relativePath = $this->toRelativePath($path);

            foreach ($regexes as $regex) {
                if (preg_match($regex, $relativePath) === 1) {
                    $matches[] = $path;
                    break;
                }
            }
        }

        return array_values(array_unique($matches));
    }

    /**
     * @param array<int, string> $regexPatterns
     *
     * @return array<int, array{line:int,evidence:string}>
     */
    public function findRegexMatches(string $path, array $regexPatterns, int $maxMatches = 3): array
    {
        if ($regexPatterns === []) {
            return [];
        }

        if (!is_file($path)) {
            return [];
        }

        // Check file size before reading
        $fileSize = filesize($path);
        if ($fileSize === false || $fileSize > 1048576) { // 1MB limit
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $matches = [];

        foreach ($regexPatterns as $pattern) {
            // Validate regex pattern before use
            if (!$this->isValidRegexPattern($pattern)) {
                continue;
            }

            // Use set_error_handler to catch regex errors without suppression
            $regexError = false;
            set_error_handler(function () use (&$regexError) {
                $regexError = true;
            });

            $result = preg_match_all('/' . $pattern . '/m', $contents, $captured, PREG_OFFSET_CAPTURE);

            restore_error_handler();

            if ($result === false || $regexError) {
                continue;
            }

            foreach ($captured[0] as $match) {
                [$text, $offset] = $match;
                $line = $this->offsetToLineNumber($contents, $offset);

                $matches[] = [
                    'line' => $line,
                    'evidence' => trim((string) $text),
                ];

                if (count($matches) >= $maxMatches) {
                    break 2;
                }
            }
        }

        return $matches;
    }

    public function toRelativePath(string $absolutePath): string
    {
        $root = $this->context->getRootPath();

        if (str_starts_with($absolutePath, $root)) {
            return ltrim(substr($absolutePath, strlen($root)), DIRECTORY_SEPARATOR);
        }

        return $absolutePath;
    }

    private function convertGlobToRegex(string $pattern): string
    {
        $pattern = ltrim(str_replace('\\', '/', $pattern), '/');
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace(['\*\*', '\*', '\?'], ['.*', '[^/]*', '.'], $pattern);

        return '#^' . $pattern . '$#i';
    }

    private function offsetToLineNumber(string $contents, int $offset): int
    {
        return substr_count(substr($contents, 0, $offset), "\n") + 1;
    }

    /**
     * Validate that a regex pattern is safe to use.
     * Checks for patterns that could cause ReDoS attacks.
     */
    private function isValidRegexPattern(string $pattern): bool
    {
        // Check pattern isn't empty
        if ($pattern === '') {
            return false;
        }

        // Check for catastrophic backtracking patterns
        // Reject patterns with nested quantifiers like (a+)+ or (a*)*
        if (preg_match('/\([^)]*[*+]\)[*+]/', $pattern)) {
            return false;
        }

        // Test the pattern with a simple validation
        set_error_handler(function () {
            // Silently catch errors
        });

        $valid = @preg_match('/' . $pattern . '/', '') !== false;

        restore_error_handler();

        return $valid;
    }
}
