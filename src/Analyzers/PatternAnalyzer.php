<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Analyzers;

use GoldenPathDigital\LaravelAscend\Documentation\DocumentationException;
use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;

final class PatternAnalyzer
{
    /** @var KnowledgeBaseService */
    private $knowledgeBase;

    /** @var FilesystemScanner */
    private $scanner;

    public function __construct(
        KnowledgeBaseService $knowledgeBase,
        FilesystemScanner $scanner,
    ) {
        $this->knowledgeBase = $knowledgeBase;
        $this->scanner = $scanner;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function analyzePattern(string $patternId): array
    {
        $pattern = $this->knowledgeBase->getPattern($patternId);

        /** @var array<int, string> $filePatterns */
        $filePatterns = $pattern['detection']['file_patterns'] ?? $pattern['detection']['paths'] ?? [];

        /** @var array<int, string> $regexPatterns */
        $regexPatterns = $pattern['detection']['regex_patterns'] ?? [];
        /** @var array<int, string> $contentPatterns */
        $contentPatterns = $pattern['detection']['content_patterns'] ?? [];

        $regexes = array_merge($regexPatterns, array_map(static fn (string $value): string => preg_quote($value, '/'), $contentPatterns));

        $files = $filePatterns !== []
            ? $this->scanner->findByPatterns($filePatterns)
            : $this->scanner->allFiles();

        $results = [];

        foreach ($files as $path) {
            $evidence = $this->scanner->findRegexMatches($path, $regexes);

            if ($regexes !== [] && $evidence === []) {
                continue;
            }

            $results[] = [
                'file' => $this->scanner->toRelativePath($path),
                'evidence' => $evidence,
            ];
        }

        return $results;
    }

    /**
     * @param array<int, string> $patternIds
     * @return array<int, array<string, mixed>>
     */
    public function analyzeAll(array $patternIds): array
    {
        $results = [];

        foreach ($patternIds as $patternId) {
            try {
                $matches = $this->analyzePattern((string) $patternId);

                if ($matches !== []) {
                    $results[] = [
                        'pattern_id' => $patternId,
                        'matches' => $matches,
                    ];
                }
            } catch (DocumentationException) {
                continue;
            }
        }

        return $results;
    }
}
