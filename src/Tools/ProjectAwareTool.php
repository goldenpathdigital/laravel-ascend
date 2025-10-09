<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools;

use GoldenPathDigital\LaravelAscend\Analyzers\FilesystemScanner;
use GoldenPathDigital\LaravelAscend\Analyzers\PatternAnalyzer;
use GoldenPathDigital\LaravelAscend\Analyzers\ProjectAnalyzer;
use GoldenPathDigital\LaravelAscend\Analyzers\ProjectContext;
use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use function base_path;

abstract class ProjectAwareTool extends AbstractTool
{
    /** @var KnowledgeBaseService */
    protected $knowledgeBase;

    protected ProjectAnalyzer $projectAnalyzer;

    public function __construct(
        KnowledgeBaseService $knowledgeBase
    ) {
        $this->knowledgeBase = $knowledgeBase;
        $this->projectAnalyzer = new ProjectAnalyzer($knowledgeBase);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function createContext(array $payload): ProjectContext
    {
        $root = $payload['project_root'] ?? base_path();

        return new ProjectContext((string) $root);
    }

    protected function createScanner(ProjectContext $context): FilesystemScanner
    {
        return new FilesystemScanner($context);
    }

    protected function createPatternAnalyzer(ProjectContext $context): PatternAnalyzer
    {
        return new PatternAnalyzer($this->knowledgeBase, $this->createScanner($context));
    }

    /**
     * Common schema definition for tools that operate on the local project.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function baseProjectProperties(): array
    {
        return [
            'project_root' => [
                'type' => 'string',
                'description' => 'Absolute path to the Laravel project root. Defaults to the current working project (`base_path()`) when omitted.',
            ],
        ];
    }

    /**
     * Schema definitions for version range inputs shared across upgrade tools.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function upgradeRangeProperties(): array
    {
        $versionDescription = 'Laravel version identifier, such as "10", "10.x", or "10.3.0".';

        return [
            'from' => [
                'type' => 'string',
                'description' => 'Starting Laravel version for the upgrade (defaults to the project\'s current major).',
            ],
            'to' => [
                'type' => 'string',
                'description' => 'Target Laravel version for the upgrade.',
            ],
            'target' => [
                'type' => 'string',
                'description' => 'Alias for the target Laravel version (e.g. "11" or "11.x").',
            ],
            'target_version' => [
                'type' => 'string',
                'description' => 'Alternative snake_case key for the target Laravel version.',
                'deprecated' => true,
            ],
            'targetVersion' => [
                'type' => 'string',
                'description' => 'Alternative camelCase key for the target Laravel version.',
                'deprecated' => true,
            ],
            'target_laravel_version' => [
                'type' => 'string',
                'description' => 'Explicit Laravel target version (used by some agents).',
            ],
            'targetLaravelVersion' => [
                'type' => 'string',
                'description' => 'CamelCase alias for the Laravel target version.',
                'deprecated' => true,
            ],
            'to_version' => [
                'type' => 'string',
                'description' => 'Alternative key for the target version when providing explicit ranges.',
                'deprecated' => true,
            ],
            'toVersion' => [
                'type' => 'string',
                'description' => 'CamelCase alias for the target version.',
                'deprecated' => true,
            ],
            'context' => [
                'type' => 'object',
                'description' => 'Optional nested context payload that may contain "from" and "to" keys supplied by some agents.',
                'properties' => [
                    'from' => [
                        'type' => 'string',
                        'description' => $versionDescription,
                    ],
                    'to' => [
                        'type' => 'string',
                        'description' => $versionDescription,
                    ],
                ],
                'additionalProperties' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{from:string,to:string,identifier:?string}
     */
    protected function resolveUpgradeRange(ProjectContext $context, array $payload): array
    {
        $from = $this->extractVersionValue($payload, ['from', 'from_version', 'current', 'current_version']);
        $to = $this->extractVersionValue($payload, ['to', 'to_version']);
        $targetOverride = $to ?? $this->extractVersionValue($payload, [
            'target',
            'target_version',
            'targetVersion',
            'target_laravel_version',
            'targetLaravelVersion',
            'to',
            'to_version',
            'toVersion',
        ]);

        $upgrade = $this->projectAnalyzer->getUpgradePath($context, $targetOverride);
        $upgradePath = $upgrade['upgrade_path'] ?? [];
        $identifier = is_string($upgradePath['identifier'] ?? null) ? $upgradePath['identifier'] : null;

        $defaultFrom = null;
        $defaultTo = null;

        if ($identifier !== null && preg_match('/^(\d+)-to-(\d+)$/', $identifier, $matches) === 1) {
            $defaultFrom = $this->formatMajorVersion((int) $matches[1]);
            $defaultTo = $this->formatMajorVersion((int) $matches[2]);
        }

        $resolvedFrom = $this->normalizeVersion($from) ?? $defaultFrom ?? '';
        $resolvedTo = $this->normalizeVersion($to) ?? $defaultTo ?? '';

        return [
            'from' => $resolvedFrom,
            'to' => $resolvedTo,
            'identifier' => $identifier,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $keys
     */
    private function extractVersionValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $this->sanitizeVersionValue($payload[$key]);
            }
        }

        if (isset($payload['context']) && is_array($payload['context'])) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $payload['context'])) {
                    return $this->sanitizeVersionValue($payload['context'][$key]);
                }
            }
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private function sanitizeVersionValue($value): ?string
    {
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeVersion(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/^\d+(\.x)?$/', $value) === 1) {
            return str_contains($value, '.x') ? $value : $value . '.x';
        }

        if (preg_match('/^\d+\.\d+(\.\d+)?$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/(\d{1,2})/', $value, $matches) === 1) {
            return $matches[1] . '.x';
        }

        return $value;
    }

    private function formatMajorVersion(int $major): string
    {
        return sprintf('%d.x', $major);
    }

    /**
     * Recommendations to capture a baseline before performing upgrades.
     *
     * @return array<int, string>
     */
    protected function baselineRecommendations(): array
    {
        return [
            'Run the full automated test suite and record its results to capture a pass/fail baseline.',
            'Capture key performance metrics (response time, throughput, error rates) from monitoring or synthetic load tests.',
            'Take a fresh backup of the database and any shared storage assets before applying changes.',
            'Ensure the git working tree is clean and tag or note the current commit for quick rollback if needed.',
            'Document current environment details (PHP version, extensions, queue workers, cron jobs) for comparison after the upgrade.',
        ];
    }
}
