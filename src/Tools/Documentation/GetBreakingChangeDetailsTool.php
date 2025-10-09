<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Documentation;

use GoldenPathDigital\LaravelAscend\Documentation\DocumentationException;
use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Tools\AbstractTool;

final class GetBreakingChangeDetailsTool extends AbstractTool
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
        return 'get_breaking_change_details';
    }

    public function getDescription(): string
    {
        return 'Fetch the details for a specific breaking change in a given Laravel release.';
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $changeId = isset($payload['id']) ? (string) $payload['id'] : '';
        $version = isset($payload['version']) ? (string) $payload['version'] : '';

        if ($changeId === '' || $version === '') {
            return $this->error('Parameters "id" and "version" are required.', startedAt: $startedAt, code: 'invalid_request');
        }

        try {
            $slug = $this->knowledgeBase->resolveBreakingChangeSlug($version);
            $document = $this->knowledgeBase->getBreakingChangeDocument($slug);
            $entry = $this->knowledgeBase->getBreakingChangeEntry($slug, $changeId);
        } catch (DocumentationException $exception) {
            return $this->error($exception->getMessage(), startedAt: $startedAt, code: 'not_found');
        }

        $data = [
            'slug' => $slug,
            'version' => $document['version'] ?? $version,
            'from_version' => $document['from_version'] ?? null,
            'change' => $entry,
        ];

        return $this->success($data, startedAt: $startedAt);
    }
}
