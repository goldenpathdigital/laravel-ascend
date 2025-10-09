<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Documentation;

final class SearchIndex
{
    /** @var DocumentationLoader */
    private $loader;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $entries;

    public function __construct(DocumentationLoader $loader)
    {
        $this->loader = $loader;
        $this->entries = $this->buildEntries();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 10): array
    {
        $query = trim($query);

        if ($query === '' || $limit <= 0) {
            return [];
        }

        $terms = array_values(array_filter(array_map(
            static fn (string $term): string => strtolower($term),
            preg_split('/\s+/', $query) ?: [],
        )));

        if ($terms === []) {
            return [];
        }

        $results = [];

        foreach ($this->entries as $entry) {
            $score = 0;

            foreach ($terms as $term) {
                if (str_contains($entry['search_tokens'], $term)) {
                    $score++;
                }
            }

            if ($score === 0) {
                continue;
            }

            $results[] = [
                'type' => $entry['type'],
                'id' => $entry['id'],
                'title' => $entry['title'],
                'summary' => $entry['summary'],
                'metadata' => $entry['metadata'],
                'score' => $score,
            ];
        }

        usort($results, static function (array $left, array $right): int {
            $scoreComparison = $right['score'] <=> $left['score'];

            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            $typeComparison = strcmp($left['type'], $right['type']);

            if ($typeComparison !== 0) {
                return $typeComparison;
            }

            return strcmp($left['title'], $right['title']);
        });

        return array_slice($results, 0, $limit);
    }

    public function getEntryCount(): int
    {
        return count($this->entries);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildEntries(): array
    {
        $entries = [];

        foreach ($this->loader->loadBreakingChangeEntries() as $identifier => $change) {
            $summary = $this->createSummary($change['description'] ?? '');
            $metadata = [
                'slug' => $change['slug'] ?? null,
                'version' => $change['version'] ?? null,
                'severity' => $change['severity'] ?? null,
                'category' => $change['category'] ?? null,
            ];

            $entries[] = $this->createEntry(
                type: 'breaking_change',
                id: $identifier,
                title: (string) ($change['title'] ?? $change['id']),
                summary: $summary,
                metadata: $metadata,
                additionalTokens: [
                    (string) ($change['id'] ?? ''),
                    (string) ($change['severity'] ?? ''),
                    (string) ($change['category'] ?? ''),
                    (string) ($change['version'] ?? ''),
                ],
            );
        }

        foreach ($this->loader->loadPatternDocuments() as $patternId => $pattern) {
            $summary = $this->createSummary($pattern['description'] ?? '');
            $metadata = [
                'category' => $pattern['category'] ?? null,
                'complexity' => $pattern['complexity'] ?? null,
                'applies_to_versions' => $pattern['applies_to_versions'] ?? [],
            ];

            $entries[] = $this->createEntry(
                type: 'pattern',
                id: (string) $patternId,
                title: (string) ($pattern['name'] ?? $patternId),
                summary: $summary,
                metadata: $metadata,
                additionalTokens: array_merge(
                    (array) ($pattern['applies_to_versions'] ?? []),
                    [
                        (string) ($pattern['category'] ?? ''),
                        (string) ($pattern['complexity'] ?? ''),
                    ],
                ),
            );
        }

        return $entries;
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, string> $additionalTokens
     *
     * @return array<string, mixed>
     */
    private function createEntry(
        string $type,
        string $id,
        string $title,
        string $summary,
        array $metadata,
        array $additionalTokens = [],
    ): array {
        $tokenSource = strtolower(
            trim(
                implode(
                    ' ',
                    array_filter([
                        $title,
                        $summary,
                        implode(' ', array_filter($additionalTokens)),
                    ]),
                ),
            ),
        );

        return [
            'type' => $type,
            'id' => $id,
            'title' => $title,
            'summary' => $summary,
            'metadata' => $metadata,
            'search_tokens' => $tokenSource,
        ];
    }

    private function createSummary(string $text): string
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return '';
        }

        if ($this->stringLength($trimmed) <= 200) {
            return $trimmed;
        }

        return rtrim($this->stringSubstring($trimmed, 0, 197)) . '...';
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function stringSubstring(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, $start, $length)
            : substr($value, $start, $length);
    }
}
