<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Analyzers;

use GoldenPathDigital\LaravelAscend\Documentation\DocumentationException;

final class ProjectContext
{
    private string $rootPath;

    /**
     * @var array<string, bool>
     */
    private array $excludedDirectories;

    /**
     * @param array<int, string> $excludedDirectories
     */
    public function __construct(string $rootPath, array $excludedDirectories = ['vendor', 'node_modules', 'storage', '.git'])
    {
        $resolved = realpath($rootPath);

        if ($resolved === false || !is_dir($resolved)) {
            throw DocumentationException::becauseBasePathIsInvalid($rootPath);
        }

        $this->rootPath = $resolved;
        $this->excludedDirectories = array_fill_keys($excludedDirectories, true);
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function isExcluded(string $relativePath): bool
    {
        $segments = explode(DIRECTORY_SEPARATOR, $relativePath);
        $segments = array_filter($segments);

        foreach ($segments as $segment) {
            if (isset($this->excludedDirectories[$segment])) {
                return true;
            }
        }

        return false;
    }

    public function resolvePath(string $relativePath): string
    {
        $relative = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $candidate = $this->rootPath . DIRECTORY_SEPARATOR . $relative;

        return $candidate;
    }
}
