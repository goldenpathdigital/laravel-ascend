<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Documentation;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class DocumentationLoader
{
    private DocumentationParser $parser;
    private LoggerInterface $logger;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $index = null;

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $breakingChangeDocuments = null;

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $patternDocuments = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $upgradePaths = null;

    private string $basePath;

    public function __construct(string $basePath, ?DocumentationParser $parser = null, ?LoggerInterface $logger = null)
    {
        $resolvedBasePath = realpath($basePath);

        if ($resolvedBasePath === false || !is_dir($resolvedBasePath)) {
            throw DocumentationException::becauseBasePathIsInvalid($basePath);
        }

        $this->basePath = $resolvedBasePath;
        $this->parser = $parser ?? new DocumentationParser();
        $this->logger = $logger ?? new NullLogger();

        $this->logger->debug('DocumentationLoader initialized', ['base_path' => $this->basePath]);
    }

    /**
     * @return array<string, mixed>
     */
    public function loadIndex(): array
    {
        if ($this->index === null) {
            $this->index = $this->parser->parseJsonFile($this->resolvePath('index.json'));
        }

        return $this->index;
    }

    /**
     * @return array<int, string>
     */
    public function getLaravelVersionsCovered(): array
    {
        $index = $this->loadIndex();

        /** @var array<int, string> $versions */
        $versions = $index['laravel_versions_covered'] ?? [];

        return $versions;
    }

    public function getKnowledgeBaseVersion(): ?string
    {
        $index = $this->loadIndex();

        /** @var string|null $version */
        $version = $index['knowledge_base_version'] ?? null;

        return $version;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function loadBreakingChangeDocuments(): array
    {
        if ($this->breakingChangeDocuments !== null) {
            return $this->breakingChangeDocuments;
        }

        $index = $this->loadIndex();

        /** @var array<string, array<string, mixed>> $files */
        $files = $index['breaking_changes_files'] ?? [];
        $documents = [];

        foreach ($files as $slug => $metadata) {
            if (!is_array($metadata) || !isset($metadata['file'])) {
                continue;
            }

            $path = $this->resolvePath((string) $metadata['file']);
            $documents[$slug] = $this->parser->parseJsonFile($path);
        }

        $this->breakingChangeDocuments = $documents;

        return $this->breakingChangeDocuments;
    }

    /**
     * @return array<string, mixed>
     */
    public function loadBreakingChangeDocument(string $slug): array
    {
        $documents = $this->loadBreakingChangeDocuments();

        if (!isset($documents[$slug])) {
            throw DocumentationException::becauseDocumentNotFound('breaking change document', $slug);
        }

        return $documents[$slug];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function loadPatternDocuments(): array
    {
        if ($this->patternDocuments !== null) {
            return $this->patternDocuments;
        }

        $patternDirectory = $this->resolvePath('patterns');
        $patternFiles = glob($patternDirectory . DIRECTORY_SEPARATOR . '*.json');

        if ($patternFiles === false) {
            throw DocumentationException::becauseFileCouldNotBeParsed($patternDirectory, 'failed to glob pattern files');
        }

        $documents = [];

        foreach ($patternFiles as $file) {
            if (!is_string($file)) {
                continue;
            }

            $decoded = $this->parser->parseJsonFile($file);

            $patternId = $decoded['pattern_id'] ?? pathinfo($file, PATHINFO_FILENAME);

            if (!is_string($patternId) || $patternId === '') {
                $patternId = pathinfo($file, PATHINFO_FILENAME);
            }

            $documents[$patternId] = $decoded;
        }

        ksort($documents);

        $this->patternDocuments = $documents;

        return $this->patternDocuments;
    }

    /**
     * @return array<string, mixed>
     */
    public function loadPatternDocument(string $patternId): array
    {
        $documents = $this->loadPatternDocuments();

        if (!isset($documents[$patternId])) {
            throw DocumentationException::becauseDocumentNotFound('pattern', $patternId);
        }

        return $documents[$patternId];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function loadBreakingChangeEntries(): array
    {
        $entries = [];

        foreach ($this->loadBreakingChangeDocuments() as $slug => $document) {
            /** @var array<int, array<string, mixed>> $changes */
            $changes = $document['breaking_changes'] ?? [];

            foreach ($changes as $change) {
                if (!is_array($change) || !isset($change['id'])) {
                    continue;
                }

                $changeId = (string) $change['id'];
                $entryKey = sprintf('%s::%s', $slug, $changeId);

                $entries[$entryKey] = [
                    'id' => $changeId,
                    'slug' => $slug,
                    'title' => $change['title'] ?? $changeId,
                    'version' => $document['version'] ?? null,
                    'severity' => $change['severity'] ?? null,
                    'category' => $change['category'] ?? null,
                    'description' => $change['description'] ?? '',
                    'data' => $change,
                ];
            }
        }

        ksort($entries);

        return $entries;
    }

    /**
     * @return array<string, mixed>
     */
    public function loadUpgradePaths(): array
    {
        if ($this->upgradePaths === null) {
            $this->upgradePaths = $this->parser->parseJsonFile(
                $this->resolvePath('upgrade-paths/upgrade-paths.json'),
            );
        }

        return $this->upgradePaths;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    private function resolvePath(string $relativePath): string
    {
        $normalised = $this->normaliseRelativePath($relativePath);

        // Validate for path traversal attempts before resolving
        if (str_contains($normalised, '..')) {
            $this->logger->warning('Path traversal attempt detected', [
                'relative_path' => $relativePath,
                'normalised' => $normalised,
                'base_path' => $this->basePath,
            ]);
            throw DocumentationException::becauseFileIsMissing($relativePath);
        }

        $candidate = $this->basePath . DIRECTORY_SEPARATOR . $normalised;
        $real = realpath($candidate);

        if ($real === false) {
            $this->logger->debug('File not found', ['candidate' => $candidate]);
            throw DocumentationException::becauseFileIsMissing($candidate);
        }

        $baseWithSeparator = $this->basePath . DIRECTORY_SEPARATOR;

        // Verify resolved path is within base directory
        if (
            $real !== $this->basePath
            && !str_starts_with($real, $baseWithSeparator)
        ) {
            $this->logger->warning('Path escape attempt detected', [
                'candidate' => $candidate,
                'real' => $real,
                'base_path' => $this->basePath,
            ]);
            throw DocumentationException::becauseFileIsMissing($candidate);
        }

        return $real;
    }

    private function normaliseRelativePath(string $relativePath): string
    {
        $relative = str_replace('\\', '/', $relativePath);
        $relative = ltrim($relative, '/');

        if ($relative === '') {
            return '';
        }

        if (str_starts_with($relative, 'data/')) {
            $relative = substr($relative, 5);
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }
}
