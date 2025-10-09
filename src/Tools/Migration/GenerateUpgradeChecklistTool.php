<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Migration;

use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class GenerateUpgradeChecklistTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'generate_upgrade_checklist';
    }

    public function getDescription(): string
    {
        return 'Produce a checklist of tasks required to upgrade between Laravel versions.';
    }

    public function getInputSchema(): array
    {
        $properties = array_merge(
            $this->baseProjectProperties(),
            $this->upgradeRangeProperties()
        );

        $schema = $this->buildSchema($properties);
        $schema['anyOf'] = [
            ['required' => ['from', 'to']],
            ['required' => ['project_root']],
        ];

        return $schema;
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);

        $range = $this->resolveUpgradeRange($context, $payload);
        $from = $range['from'];
        $to = $range['to'];

        if ($from === '' || $to === '') {
            return $this->error('Unable to determine upgrade range. Provide "from" and "to" versions.', [], $startedAt, 'invalid_request');
        }

        if ($from === $to) {
            return $this->error('Current version already matches target version.', [], $startedAt, 'invalid_request');
        }

        $path = $this->knowledgeBase->getUpgradePathByVersions($from, $to);

        $checklist = [];

        /** @var array<int, array<string, mixed>> $sequence */
        $sequence = $path['sequence'] ?? [];

        foreach ($sequence as $step) {
            $checklist[] = [
                'step' => $step['step'] ?? null,
                'action' => $step['action'] ?? null,
                'required' => $step['required'] ?? false,
                'description' => $step['description'] ?? '',
                'automatable' => $step['automatable'] ?? false,
            ];
        }

        return $this->success(
            [
                'from' => $path['from'] ?? $from,
                'to' => $path['to'] ?? $to,
                'difficulty' => $path['difficulty'] ?? null,
                'estimated_time_minutes' => $path['estimated_time_minutes'] ?? null,
                'checklist' => $checklist,
                'baseline_recommendations' => $this->baselineRecommendations(),
            ],
            [],
            $startedAt
        );
    }
}
