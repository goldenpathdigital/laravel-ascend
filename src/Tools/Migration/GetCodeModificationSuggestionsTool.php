<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Migration;

use GoldenPathDigital\LaravelAscend\Documentation\DocumentationException;
use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class GetCodeModificationSuggestionsTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'get_code_modification_suggestions';
    }

    public function getDescription(): string
    {
        return 'Retrieve suggested code modifications for a breaking change or pattern.';
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);

        $changeId = isset($payload['change']) ? (string) $payload['change'] : '';
        $version = isset($payload['version']) ? (string) $payload['version'] : null;

        if ($changeId === '') {
            return $this->error('Parameter "change" is required.', startedAt: $startedAt, code: 'invalid_request');
        }

        try {
            $details = $this->findChangeDetails($changeId, $version);
        } catch (DocumentationException $exception) {
            return $this->error($exception->getMessage(), startedAt: $startedAt, code: 'not_found');
        }

        $payloadData = [
            'change' => $changeId,
            'version' => $details['version'] ?? $version,
            'transformation' => $details['change']['data']['transformation'] ?? null,
            'examples' => $details['change']['data']['examples'] ?? null,
            'references' => $details['change']['references'] ?? [],
        ];

        if (isset($payload['file'])) {
            $payloadData['file'] = $payload['file'];
        }

        return $this->success($payloadData, startedAt: $startedAt);
    }

    /**
     * @return array<string, mixed>
     */
    private function findChangeDetails(string $changeId, ?string $version): array
    {
        if ($version !== null) {
            $slug = $this->knowledgeBase->resolveBreakingChangeSlug($version);
            $document = $this->knowledgeBase->getBreakingChangeDocument($slug);
            $entry = $this->knowledgeBase->getBreakingChangeEntry($slug, $changeId);

            return [
                'slug' => $slug,
                'version' => $document['version'] ?? $version,
                'change' => $entry,
            ];
        }

        foreach ($this->knowledgeBase->listBreakingChangeSlugs() as $slug) {
            $document = $this->knowledgeBase->getBreakingChangeDocument($slug);
            $entryKey = null;

            /** @var array<int, array<string, mixed>> $changes */
            $changes = $document['breaking_changes'] ?? [];

            foreach ($changes as $candidate) {
                if (($candidate['id'] ?? null) === $changeId) {
                    $entryKey = $slug;
                    break;
                }
            }

            if ($entryKey !== null) {
                $entry = $this->knowledgeBase->getBreakingChangeEntry($slug, $changeId);

                return [
                    'slug' => $slug,
                    'version' => $document['version'] ?? null,
                    'change' => $entry,
                ];
            }
        }

        throw DocumentationException::becauseDocumentNotFound('breaking change entry', $changeId);
    }
}
