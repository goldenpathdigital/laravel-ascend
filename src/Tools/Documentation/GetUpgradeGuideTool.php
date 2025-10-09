<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Documentation;

use GoldenPathDigital\LaravelAscend\Documentation\DocumentationException;
use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Tools\AbstractTool;

final class GetUpgradeGuideTool extends AbstractTool
{
    /** @var KnowledgeBaseService */
    private $knowledgeBase;

    public function __construct(
        KnowledgeBaseService $knowledgeBase,
    ) {
        $this->knowledgeBase = $knowledgeBase;
    }

    public function getName(): string
    {
        return 'get_upgrade_guide';
    }

    public function getDescription(): string
    {
        return 'Retrieve the upgrade guide, required steps, and related patterns for a Laravel version transition.';
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $from = isset($payload['from']) ? (string) $payload['from'] : '';
        $to = isset($payload['to']) ? (string) $payload['to'] : '';

        if ($from === '' || $to === '') {
            return $this->error('Parameters "from" and "to" are required.', startedAt: $startedAt, code: 'invalid_request');
        }

        try {
            $identifier = $this->knowledgeBase->resolveUpgradePathIdentifier($from, $to);
            $path = $this->knowledgeBase->getUpgradePath($identifier);
        } catch (DocumentationException $exception) {
            return $this->error($exception->getMessage(), startedAt: $startedAt, code: 'not_found');
        }

        $warnings = [];
        $requiredPatternDetails = $this->mapPatternDetails($path['required_patterns'] ?? [], $warnings);
        $optionalPatternDetails = $this->mapPatternDetails($path['optional_patterns'] ?? [], $warnings);

        $data = [
            'identifier' => $identifier,
            'from' => $path['from'] ?? $from,
            'to' => $path['to'] ?? $to,
            'difficulty' => $path['difficulty'] ?? null,
            'estimated_time_minutes' => $path['estimated_time_minutes'] ?? null,
            'breaking_changes_file' => $path['breaking_changes_file'] ?? null,
            'required_patterns' => [
                'ids' => $path['required_patterns'] ?? [],
                'details' => $requiredPatternDetails,
            ],
            'optional_patterns' => [
                'ids' => $path['optional_patterns'] ?? [],
                'details' => $optionalPatternDetails,
            ],
            'sequence' => $path['sequence'] ?? [],
        ];

        return $this->success($data, $warnings, $startedAt);
    }

    /**
     * @param array<int, string> $patternIds
     * @param array<int, string> $warnings
     *
     * @return array<int, array<string, mixed>>
     */
    private function mapPatternDetails(array $patternIds, array &$warnings): array
    {
        $details = [];

        foreach ($patternIds as $patternId) {
            try {
                $details[] = $this->knowledgeBase->getPattern((string) $patternId);
            } catch (DocumentationException $exception) {
                $warnings[] = $exception->getMessage();
            }
        }

        return $details;
    }
}
