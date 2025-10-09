<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Analysis;

use GoldenPathDigital\LaravelAscend\Analyzers\BreakingChangeAnalyzer;
use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class ScanBreakingChangesTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'scan_breaking_changes';
    }

    public function getDescription(): string
    {
        return 'Scan the project for potential breaking changes when upgrading between Laravel versions.';
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
            ['required' => ['target']],
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

        $scanner = $this->createScanner($context);
        $analyzer = new BreakingChangeAnalyzer($this->knowledgeBase, $scanner);

        $results = $analyzer->analyze($from, $to);

        return $this->success(
            [
                'from' => $from,
                'to' => $to,
                'matches' => $results,
            ],
            [],
            $startedAt
        );
    }
}
