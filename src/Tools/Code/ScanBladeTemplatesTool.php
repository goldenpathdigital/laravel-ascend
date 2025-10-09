<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Code;

use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class ScanBladeTemplatesTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'scan_blade_templates';
    }

    public function getDescription(): string
    {
        return 'Review Blade templates for deprecated directives and risky patterns.';
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

        $bladeFiles = $scanner->findByPatterns(['resources/views/**/*.blade.php']);
        $findings = [];

        foreach ($bladeFiles as $path) {
            $evidence = $scanner->findRegexMatches($path, [
                '@php',
                '\{\{\{',
                '@inject',
            ]);

            $findings[] = [
                'file' => $scanner->toRelativePath($path),
                'issues' => $evidence,
            ];
        }

        return $this->success(
            [
                'templates' => $findings,
                'count' => count($findings),
            ],
            [],
            $startedAt
        );
    }
}
