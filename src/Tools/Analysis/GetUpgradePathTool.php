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

    public function getInputSchema(): array
    {
        $properties = array_merge(
            $this->baseProjectProperties(),
            $this->upgradeRangeProperties(),
            [
                'from_version' => [
                    'type' => 'string',
                    'description' => 'Explicit starting version when requesting a custom range (e.g. "9" or "9.x").',
                ],
                'to_version' => [
                    'type' => 'string',
                    'description' => 'Explicit target version when requesting a custom range.',
                ],
            ]
        );

        $schema = $this->buildSchema($properties);
        $schema['anyOf'] = [
            ['required' => ['from_version', 'to_version']],
            ['required' => ['project_root']],
        ];

        return $schema;
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
        $target = $this->resolveTargetVersion($payload);

        $path = $this->projectAnalyzer->getUpgradePath($context, $target);

        return $this->success($path, [], $startedAt);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveTargetVersion(array $payload): ?string
    {
        $aliases = [
            'target',
            'target_version',
            'targetVersion',
            'target_laravel_version',
            'targetLaravelVersion',
            'to_version',
            'toVersion',
        ];

        foreach ($aliases as $key) {
            if (!isset($payload[$key])) {
                continue;
            }

            $value = $payload[$key];

            if (is_string($value)) {
                $value = trim($value);
            } elseif (is_int($value)) {
                $value = (string) $value;
            } else {
                continue;
            }

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
