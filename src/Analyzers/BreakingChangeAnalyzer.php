<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Analyzers;

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;

final class BreakingChangeAnalyzer
{
    /** @var KnowledgeBaseService */
    private $knowledgeBase;

    /** @var FilesystemScanner */
    private $scanner;

    public function __construct(
        KnowledgeBaseService $knowledgeBase,
        FilesystemScanner $scanner
    ) {
        $this->knowledgeBase = $knowledgeBase;
        $this->scanner = $scanner;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function analyze(string $fromVersion, string $toVersion): array
    {
        $slug = $this->knowledgeBase->resolveBreakingChangeSlug($toVersion);
        $document = $this->knowledgeBase->getBreakingChangeDocument($slug);

        $results = [];

        /** @var array<int, array<string, mixed>> $changes */
        $changes = $document['breaking_changes'] ?? [];

        foreach ($changes as $change) {
            if (!is_array($change)) {
                continue;
            }

            $detected = $this->evaluateChange($change);

            if ($detected === []) {
                continue;
            }

            $results[] = [
                'id' => $change['id'] ?? null,
                'title' => $change['title'] ?? null,
                'severity' => $change['severity'] ?? null,
                'category' => $change['category'] ?? null,
                'automatable' => $change['automatable'] ?? false,
                'detections' => $detected,
                'references' => $change['references'] ?? [],
            ];
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $change
     *
     * @return array<int, array<string, mixed>>
     */
    private function evaluateChange(array $change): array
    {
        $detection = $change['detection'] ?? null;

        if (!is_array($detection)) {
            return [];
        }

        /** @var array<int, string> $filePatterns */
        $filePatterns = $detection['file_patterns'] ?? [];

        /** @var array<int, string> $regexPatterns */
        $regexPatterns = $detection['regex_patterns'] ?? [];

        $matches = [];

        $files = $filePatterns !== []
            ? $this->scanner->findByPatterns($filePatterns)
            : $this->scanner->allFiles();

        foreach ($files as $path) {
            $evidence = $this->scanner->findRegexMatches($path, $regexPatterns);

            if ($regexPatterns !== [] && $evidence === []) {
                continue;
            }

            $matches[] = [
                'file' => $this->scanner->toRelativePath($path),
                'evidence' => $evidence,
            ];
        }

        return $matches;
    }
}
