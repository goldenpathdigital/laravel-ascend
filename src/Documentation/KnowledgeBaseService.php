<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Documentation;

use GoldenPathDigital\LaravelAscend\Cache\CacheManager;

final class KnowledgeBaseService
{
    /** @var DocumentationLoader */
    private $loader;
    
    /** @var SearchIndex */
    private $searchIndex;
    
    /** @var CacheManager */
    private $cache;
    
    public function __construct(
        DocumentationLoader $loader,
        SearchIndex $searchIndex
    ) {
        $this->loader = $loader;
        $this->searchIndex = $searchIndex;
        $this->cache = new CacheManager(3600, 200); // 1 hour TTL, 200 items max
    }

    public static function createDefault(?string $basePath = null): self
    {
        $baseDirectory = $basePath ?? dirname(__DIR__, 2) . '/resources/knowledge-base';

        $loader = new DocumentationLoader($baseDirectory);
        $searchIndex = new SearchIndex($loader);

        return new self($loader, $searchIndex);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return $this->cache->remember('summary', function () {
            $index = $this->loader->loadIndex();

            return [
                'knowledge_base_version' => $index['knowledge_base_version'] ?? null,
                'last_updated' => $index['last_updated'] ?? null,
                'laravel_versions_covered' => $this->loader->getLaravelVersionsCovered(),
                'base_path' => $this->loader->getBasePath(),
                'pattern_count' => count($this->loader->loadPatternDocuments()),
                'breaking_change_document_count' => count($this->loader->loadBreakingChangeDocuments()),
                'search_entry_count' => $this->searchIndex->getEntryCount(),
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 10): array
    {
        return $this->searchIndex->search($query, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBreakingChangeDocument(string $slug): array
    {
        return $this->cache->remember("breaking_change_doc:{$slug}", function () use ($slug) {
            return $this->loader->loadBreakingChangeDocument($slug);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getBreakingChangeEntry(string $slug, string $changeId): array
    {
        $entries = $this->loader->loadBreakingChangeEntries();
        $key = sprintf('%s::%s', $slug, $changeId);

        if (!isset($entries[$key])) {
            throw DocumentationException::becauseDocumentNotFound(
                'breaking change entry',
                sprintf('%s (%s)', $changeId, $slug)
            );
        }

        return $entries[$key];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPattern(string $patternId): array
    {
        return $this->cache->remember("pattern:{$patternId}", function () use ($patternId) {
            return $this->loader->loadPatternDocument($patternId);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getUpgradePath(string $identifier): array
    {
        $upgradePaths = $this->loader->loadUpgradePaths();

        /** @var array<string, array<string, mixed>> $paths */
        $paths = $upgradePaths['upgrade_paths'] ?? [];

        if (!isset($paths[$identifier])) {
            throw DocumentationException::becauseDocumentNotFound('upgrade path', $identifier);
        }

        return $paths[$identifier];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUpgradePathByVersions(string $fromVersion, string $toVersion): array
    {
        $identifier = $this->resolveUpgradePathIdentifier($fromVersion, $toVersion);

        return $this->getUpgradePath($identifier);
    }

    /**
     * @return array<int, string>
     */
    public function listUpgradePathIdentifiers(): array
    {
        $upgradePaths = $this->loader->loadUpgradePaths();
        $paths = $upgradePaths['upgrade_paths'] ?? [];

        return array_values(array_map('strval', array_keys($paths)));
    }

    /**
     * @return array<int, string>
     */
    public function listPatternIdentifiers(): array
    {
        return array_keys($this->loader->loadPatternDocuments());
    }

    /**
     * @return array<int, string>
     */
    public function listBreakingChangeIdentifiers(): array
    {
        return array_values(array_map('strval', array_keys($this->loader->loadBreakingChangeDocuments())));
    }

    /**
     * @return array<int, string>
     */
    public function listBreakingChangeSlugs(): array
    {
        return $this->listBreakingChangeIdentifiers();
    }

    public function resolveUpgradePathIdentifier(string $fromVersion, string $toVersion): string
    {
        $from = $this->extractMajorVersion($fromVersion);
        $to = $this->extractMajorVersion($toVersion);
        $identifier = sprintf('%s-to-%s', $from, $to);

        if (!in_array($identifier, $this->listUpgradePathIdentifiers(), true)) {
            throw DocumentationException::becauseDocumentNotFound('upgrade path', $identifier);
        }

        return $identifier;
    }

    public function resolveBreakingChangeSlug(string $version): string
    {
        $targetMajor = $this->extractMajorVersion($version);

        foreach ($this->loader->loadBreakingChangeDocuments() as $slug => $document) {
            $documentVersion = (string) ($document['version'] ?? '');
            $documentMajor = $this->extractMajorVersion($documentVersion);

            if ($documentMajor === $targetMajor) {
                return $slug;
            }
        }

        throw DocumentationException::becauseDocumentNotFound('breaking change document', $version);
    }

    public function getLoader(): DocumentationLoader
    {
        return $this->loader;
    }

    public function getSearchIndex(): SearchIndex
    {
        return $this->searchIndex;
    }

    private function extractMajorVersion(string $input): string
    {
        $normalized = strtolower(trim($input));
        $normalized = str_replace(['laravel', 'v'], '', $normalized);

        if (preg_match('/(\d{1,2})/', $normalized, $matches) === 1) {
            return ltrim($matches[1], '0') ?: '0';
        }

        throw DocumentationException::becauseDocumentNotFound('version', $input);
    }
}
