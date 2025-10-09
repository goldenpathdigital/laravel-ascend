<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Code;

use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class CheckNamespaceChangesTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'check_namespace_changes';
    }

    public function getDescription(): string
    {
        return 'Detect classes under the app directory that use legacy namespaces.';
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);
        $scanner = $this->createScanner($context);

        $phpFiles = $scanner->findByPatterns(['app/**/*.php']);
        $legacy = [];

        foreach ($phpFiles as $path) {
            $matches = $scanner->findRegexMatches($path, ['^namespace\s+(?!App\\\\)[^;]+;']);

            if ($matches === []) {
                continue;
            }

            $legacy[] = [
                'file' => $scanner->toRelativePath($path),
                'namespace' => $matches[0]['evidence'] ?? null,
            ];
        }

        return $this->success(
            data: [
                'legacy_namespaces' => $legacy,
                'count' => count($legacy),
            ],
            startedAt: $startedAt,
        );
    }
}
