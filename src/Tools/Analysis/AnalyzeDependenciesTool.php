<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Analysis;

use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class AnalyzeDependenciesTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'analyze_dependencies';
    }

    public function getDescription(): string
    {
        return 'Summarise first-party and third-party Composer dependencies for the project.';
    }

    public function getInputSchema(): array
    {
        return $this->buildSchema(
            $this->baseProjectProperties()
        );
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);

        $result = $this->projectAnalyzer->analyzeDependencies($context);

        return $this->success($result, [], $startedAt);
    }
}
