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

    public function getInputSchema(): array
    {
        $properties = array_merge(
            $this->baseProjectProperties(),
            $this->upgradeRangeProperties(),
            [
                'change' => [
                    'type' => 'string',
                    'description' => 'Identifier of the breaking change to validate (e.g. "symfony-5-method-signatures").',
                ],
            ]
        );

        return $this->buildSchema($properties, ['change']);
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);

        $range = $this->resolveUpgradeRange($context, $payload);
        $from = $range['from'];
        $to = $range['to'];
        $changeId = isset($payload['change']) ? (string) $payload['change'] : '';

        if ($changeId === '') {
            return $this->error('Parameter "change" is required.', [], $startedAt, 'invalid_request');
        }

        if ($from === '' || $to === '') {
            return $this->error('Unable to determine upgrade range. Provide "from" and "to" versions.', [], $startedAt, 'invalid_request');
        }

        if ($from === $to) {
            return $this->error('Current version already matches target version.', [], $startedAt, 'invalid_request');
        }

        $scanner = $this->createScanner($context);
        $analyzer = new BreakingChangeAnalyzer($this->knowledgeBase, $scanner);

        $matches = $analyzer->analyze($from, $to);

        $changeMatches = array_values(array_filter($matches, static function (array $match) use ($changeId): bool {
            return ($match['id'] ?? null) === $changeId;
        }));

        return $this->success(
            [
                'change' => $changeId,
                'from' => $from,
                'to' => $to,
                'validated' => $changeMatches === [],
                'remaining_matches' => $changeMatches,
            ],
            [],
            $startedAt
        );
    }
}
