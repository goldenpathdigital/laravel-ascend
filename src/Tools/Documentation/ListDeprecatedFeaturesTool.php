<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Documentation;

use GoldenPathDigital\LaravelAscend\Documentation\DocumentationException;
use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Tools\AbstractTool;

final class ListDeprecatedFeaturesTool extends AbstractTool
{
    /** @var KnowledgeBaseService */
    private $knowledgeBase;

    public function __construct(
        KnowledgeBaseService $knowledgeBase
    ) {
        $this->knowledgeBase = $knowledgeBase;
    }

    public function getName(): string
    {
        return 'list_deprecated_features';
    }

    public function getDescription(): string
    {
        return 'List deprecated features and APIs for a given Laravel version.';
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $version = isset($payload['version']) ? (string) $payload['version'] : '';

        if ($version === '') {
            return $this->error('Parameter "version" is required.', [], $startedAt, 'invalid_request');
        }

        try {
            $slug = $this->knowledgeBase->resolveBreakingChangeSlug($version);
            $document = $this->knowledgeBase->getBreakingChangeDocument($slug);
        } catch (DocumentationException $exception) {
            return $this->error($exception->getMessage(), [], $startedAt, 'not_found');
        }

        $deprecated = $this->extractDeprecatedChanges($document['breaking_changes'] ?? []);
        $warnings = [];

        if ($deprecated === []) {
            $warnings[] = sprintf('No deprecated features recorded for Laravel %s.', $version);
        }

        $data = [
            'version' => $document['version'] ?? $version,
            'from_version' => $document['from_version'] ?? null,
            'deprecated' => $deprecated,
        ];

        return $this->success($data, $warnings, $startedAt);
    }

    /**
     * @param array<int, array<string, mixed>> $changes
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractDeprecatedChanges(array $changes): array
    {
        $results = [];

        foreach ($changes as $change) {
            if (!is_array($change)) {
                continue;
            }

            $title = strtolower((string) ($change['title'] ?? ''));
            $description = strtolower((string) ($change['description'] ?? ''));
            $category = strtolower((string) ($change['category'] ?? ''));

            $isDeprecated = str_contains($title, 'deprecat')
                || str_contains($description, 'deprecat')
                || in_array($category, ['deprecation', 'feature-removal'], true);

            if (!$isDeprecated) {
                continue;
            }

            $results[] = [
                'id' => $change['id'] ?? null,
                'title' => $change['title'] ?? null,
                'description' => $change['description'] ?? null,
                'severity' => $change['severity'] ?? null,
                'category' => $change['category'] ?? null,
                'references' => $change['references'] ?? [],
            ];
        }

        return $results;
    }
}
