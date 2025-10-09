<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Code;

use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class AnalyzeConfigChangesTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'analyze_config_changes';
    }

    public function getDescription(): string
    {
        return 'Summarise configuration files and highlight keys for manual review.';
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
        $scanner = $this->createScanner($context);

        $configFiles = $scanner->findByPatterns(['config/**/*.php']);
        $summaries = [];

        foreach ($configFiles as $path) {
            $data = $this->extractConfigKeys($path);

            $summaries[] = [
                'file' => $scanner->toRelativePath($path),
                'keys' => $data,
            ];
        }

        return $this->success(
            [
                'config_files' => $summaries,
                'count' => count($summaries),
            ],
            [],
            $startedAt
        );
    }

    /**
     * @return array<int, string>
     */
    private function extractConfigKeys(string $path): array
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        if (!preg_match_all('/\'([a-zA-Z0-9_.:-]+)\'\s*=>/', $contents, $matches)) {
            return [];
        }

        return array_values(array_unique($matches[1]));
    }
}
