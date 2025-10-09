<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Analysis;

use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class AnalyzeCurrentVersionTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'analyze_current_version';
    }

    public function getDescription(): string
    {
        return 'Inspect the project to determine current Laravel and PHP version constraints.';
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);

        $analysis = $this->projectAnalyzer->analyzeCurrentVersion($context);

        return $this->success($analysis, startedAt: $startedAt);
    }
}
