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

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);
        $from = isset($payload['from']) ? (string) $payload['from'] : '';
        $to = isset($payload['to']) ? (string) $payload['to'] : '';

        if ($from === '' || $to === '') {
            return $this->error('Parameters "from" and "to" are required.', startedAt: $startedAt, code: 'invalid_request');
        }

        $scanner = $this->createScanner($context);
        $analyzer = new BreakingChangeAnalyzer($this->knowledgeBase, $scanner);

        $results = $analyzer->analyze($from, $to);

        return $this->success(
            data: [
                'from' => $from,
                'to' => $to,
                'matches' => $results,
            ],
            startedAt: $startedAt
        );
    }
}

