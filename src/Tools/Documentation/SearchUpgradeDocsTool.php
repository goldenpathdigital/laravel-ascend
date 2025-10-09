<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Documentation;

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Tools\AbstractTool;

final class SearchUpgradeDocsTool extends AbstractTool
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
        return 'search_upgrade_docs';
    }

    public function getDescription(): string
    {
        return 'Search the Laravel upgrade knowledge base for relevant documents and guidance.';
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $query = isset($payload['query']) ? trim((string) $payload['query']) : '';

        if ($query === '') {
            return $this->error('Parameter "query" is required.', startedAt: $startedAt, code: 'invalid_request');
        }

        $limit = isset($payload['limit']) ? max(1, (int) $payload['limit']) : 10;

        $results = $this->knowledgeBase->search($query, $limit * 2);

        $range = $payload['range'] ?? null;

        if (is_array($range) && $range !== []) {
            $allowedTypes = array_map(static fn ($item): string => strtolower((string) $item), $range);

            $results = array_values(array_filter(
                $results,
                static fn (array $result): bool => in_array(strtolower((string) ($result['type'] ?? '')), $allowedTypes, true)
            ));
        }

        $results = array_slice($results, 0, $limit);

        return $this->success(
            data: [
                'query' => $query,
                'limit' => $limit,
                'results' => $results,
            ],
            startedAt: $startedAt
        );
    }
}
