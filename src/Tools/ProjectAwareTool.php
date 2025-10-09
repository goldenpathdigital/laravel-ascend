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
}
