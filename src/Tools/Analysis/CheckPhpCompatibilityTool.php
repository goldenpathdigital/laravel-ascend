<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Analysis;

use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class CheckPhpCompatibilityTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'check_php_compatibility';
    }

    public function getDescription(): string
    {
        return 'Validate the project PHP constraint against the requirements of a target Laravel version.';
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);

        // Support direct php_version/target_laravel_version parameters
        if (isset($payload['php_version']) && isset($payload['target_laravel_version'])) {
            $phpVersion = (string) $payload['php_version'];
            $targetVersion = (string) $payload['target_laravel_version'];

            // Get Laravel requirements for the target version
            $slug = $this->knowledgeBase->resolveBreakingChangeSlug(sprintf('%s.x', $targetVersion));
            $document = $this->knowledgeBase->getBreakingChangeDocument($slug);

            $requirements = $document['php_requirement'] ?? [];
            $isCompatible = true;
            $warnings = [];

            if (isset($requirements['minimum'])) {
                $isCompatible = version_compare($phpVersion, $requirements['minimum'], '>=');

                if (!$isCompatible) {
                    $warnings[] = sprintf('PHP %s does not satisfy minimum %s.', $phpVersion, $requirements['minimum']);
                }
            }

            $result = [
                'php_constraint' => $phpVersion,
                'requirements' => $requirements,
                'is_compatible' => $isCompatible,
                'compatible' => $isCompatible,
                'warnings' => $warnings,
            ];

            return $this->success($result, $warnings, $startedAt);
        }

        $context = $this->createContext($payload);
        $target = isset($payload['target']) ? (string) $payload['target'] : '';

        if ($target === '') {
            return $this->error('Parameter "target" is required.', [], $startedAt, 'invalid_request');
        }

        $result = $this->projectAnalyzer->checkPhpCompatibility($context, $target);

        return $this->success($result, $result['warnings'] ?? [], $startedAt);
    }
}
