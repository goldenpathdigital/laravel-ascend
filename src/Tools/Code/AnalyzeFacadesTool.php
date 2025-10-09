<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Code;

use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class AnalyzeFacadesTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'analyze_facades';
    }

    public function getDescription(): string
    {
        return 'Identify Laravel facade usages within the project.';
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);
        $scanner = $this->createScanner($context);

        $phpFiles = $scanner->findByPatterns(['app/**/*.php', 'routes/**/*.php']);
        $facades = [];

        foreach ($phpFiles as $path) {
            $matches = $scanner->findRegexMatches($path, [
                'Illuminate\\\\Support\\\\Facades\\\\[A-Z][A-Za-z]+',
                'Facade::',
            ]);

            if ($matches === []) {
                continue;
            }

            $facades[] = [
                'file' => $scanner->toRelativePath($path),
                'occurrences' => $matches,
            ];
        }

        return $this->success(
            [
                'facade_usages' => $facades,
                'count' => count($facades),
            ],
            [],
            $startedAt
        );
    }
}
