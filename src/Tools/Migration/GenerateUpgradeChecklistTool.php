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

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);

        $from = isset($payload['from']) ? (string) $payload['from'] : '';
        $to = isset($payload['to']) ? (string) $payload['to'] : '';

        if ($from === '' || $to === '') {
            return $this->error('Parameters "from" and "to" are required.', [], $startedAt, 'invalid_request');
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
            ],
            [],
            $startedAt
        );
    }
}
