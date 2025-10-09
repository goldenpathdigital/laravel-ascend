<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Migration;

use GoldenPathDigital\LaravelAscend\Analyzers\BreakingChangeAnalyzer;
use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class ValidateUpgradeStepTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'validate_upgrade_step';
    }

    public function getDescription(): string
    {
        return 'Verify whether a specific upgrade step has been completed by checking for remaining detections.';
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);

        $from = isset($payload['context']['from']) ? (string) $payload['context']['from'] : ($payload['from'] ?? '');
        $to = isset($payload['context']['to']) ? (string) $payload['context']['to'] : ($payload['to'] ?? '');
        $changeId = isset($payload['change']) ? (string) $payload['change'] : '';

        if ($from === '' || $to === '' || $changeId === '') {
            return $this->error('Parameters "from", "to", and "change" are required.', startedAt: $startedAt, code: 'invalid_request');
        }

        $scanner = $this->createScanner($context);
        $analyzer = new BreakingChangeAnalyzer($this->knowledgeBase, $scanner);

        $matches = $analyzer->analyze($from, $to);

        $changeMatches = array_values(array_filter($matches, static function (array $match) use ($changeId): bool {
            return ($match['id'] ?? null) === $changeId;
        }));

        return $this->success(
            data: [
                'change' => $changeId,
                'validated' => $changeMatches === [],
                'remaining_matches' => $changeMatches,
            ],
            startedAt: $startedAt,
        );
    }
}
