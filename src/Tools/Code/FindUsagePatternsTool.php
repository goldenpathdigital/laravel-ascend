<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Code;

use GoldenPathDigital\LaravelAscend\Analyzers\FilesystemScanner;
use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class FindUsagePatternsTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'find_usage_patterns';
    }

    public function getDescription(): string
    {
        return 'Search the project for usage patterns defined in the knowledge base or custom regex/glob combinations.';
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);
        $patternId = isset($payload['pattern']) ? (string) $payload['pattern'] : '';

        if ($patternId === '') {
            return $this->error('Parameter "pattern" is required.', startedAt: $startedAt, code: 'invalid_request');
        }

        $scanner = $this->createScanner($context);

        $results = [];
        $warnings = [];

        if ($this->isKnowledgeBasePattern($patternId)) {
            $patternAnalyzer = $this->createPatternAnalyzer($context);
            $matches = $patternAnalyzer->analyzePattern($patternId);

            $results[] = [
                'pattern_id' => $patternId,
                'matches' => $matches,
            ];
        } else {
            $glob = isset($payload['glob']) ? (string) $payload['glob'] : '**/*.php';
            $regex = $patternId;

            $matches = $this->searchWithRegex($scanner, $glob, $regex);

            if ($matches === []) {
                $warnings[] = sprintf('No matches found for regex %s within %s.', $regex, $glob);
            }

            $results[] = [
                'pattern_id' => null,
                'regex' => $regex,
                'glob' => $glob,
                'matches' => $matches,
            ];
        }

        return $this->success(
            data: [
                'results' => $results,
            ],
            warnings: $warnings,
            startedAt: $startedAt,
        );
    }

    private function isKnowledgeBasePattern(string $patternId): bool
    {
        return in_array($patternId, $this->knowledgeBase->listPatternIdentifiers(), true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchWithRegex(FilesystemScanner $scanner, string $glob, string $regex): array
    {
        $paths = $scanner->findByPatterns([$glob]);

        $results = [];

        foreach ($paths as $path) {
            $evidence = $scanner->findRegexMatches($path, [$regex]);

            if ($evidence === []) {
                continue;
            }

            $results[] = [
                'file' => $scanner->toRelativePath($path),
                'evidence' => $evidence,
            ];
        }

        return $results;
    }
}
