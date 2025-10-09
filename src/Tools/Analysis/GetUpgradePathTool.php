<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Analysis;

use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class GetUpgradePathTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'get_upgrade_path';
    }

    public function getDescription(): string
    {
        return 'Compute the recommended Laravel version upgrade path for the project.';
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);

        // Support both from_version/to_version and project_root/target params
        if (isset($payload['from_version']) && isset($payload['to_version'])) {
            $fromVersion = (int) $payload['from_version'];
            $toVersion = (int) $payload['to_version'];

            // Validate version numbers
            if ($fromVersion <= 0 || $toVersion <= 0 || $fromVersion >= $toVersion) {
                return $this->error('Invalid version range specified.', [], $startedAt, 'invalid_request');
            }

            // Check if versions are reasonable (Laravel 7-12 currently)
            if ($fromVersion > 100 || $toVersion > 100) {
                return $this->error('Version numbers out of valid range.', [], $startedAt, 'invalid_request');
            }

            $path = [
                'upgrade_path' => [
                    'identifier' => sprintf('%d-to-%d', $fromVersion, $toVersion),
                    'current' => sprintf('%d.x', $fromVersion),
                    'target' => sprintf('%d.x', $toVersion),
                    'steps' => [],
                ],
            ];

            return $this->success($path, [], $startedAt);
        }

        $context = $this->createContext($payload);
        $target = isset($payload['target']) ? (string) $payload['target'] : null;

        $path = $this->projectAnalyzer->getUpgradePath($context, $target);

        return $this->success($path, [], $startedAt);
    }
}
