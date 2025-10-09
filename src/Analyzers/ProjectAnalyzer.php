<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Analyzers;

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;

final class ProjectAnalyzer
{
    /** @var KnowledgeBaseService */
    private $knowledgeBase;

    public function __construct(
        KnowledgeBaseService $knowledgeBase
    ) {
        $this->knowledgeBase = $knowledgeBase;
    }

    /**
     * @return array<string, mixed>
     */
    public function analyzeCurrentVersion(ProjectContext $context): array
    {
        $composer = ComposerInspector::fromPath($context->getRootPath());
        $laravelConstraint = $composer->getLaravelFrameworkConstraint();

        return [
            'current_version' => $laravelConstraint,
            'php_constraint' => $composer->getPhpConstraint(),
            'laravel_constraint' => $laravelConstraint,
            'framework_info' => [
                'laravel' => $laravelConstraint,
                'php' => $composer->getPhpConstraint(),
            ],
            'packages' => $composer->getRequiredPackages(),
            'dev_packages' => $composer->getDevPackages(),
            'installed_versions' => $composer->getInstalledVersions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUpgradePath(ProjectContext $context, ?string $target = null): array
    {
        $composer = ComposerInspector::fromPath($context->getRootPath());
        $currentConstraint = $composer->getLaravelFrameworkConstraint();

        if ($currentConstraint === null) {
            return [
                'upgrade_path' => [
                    'identifier' => null,
                    'current' => null,
                    'target' => $target,
                    'steps' => [],
                ],
            ];
        }

        $currentMajor = $this->extractMajorVersion($currentConstraint);

        $targetMajor = $target !== null
            ? $this->extractMajorVersion($target)
            : $this->determineNextSupportedMajor($currentMajor);

        $pathIdentifier = sprintf('%d-to-%d', $currentMajor, $targetMajor);

        if ($targetMajor <= $currentMajor) {
            return [
                'upgrade_path' => [
                    'identifier' => $pathIdentifier,
                    'current' => $currentConstraint,
                    'target' => $target ?? sprintf('%d.x', $targetMajor),
                    'steps' => [],
                ],
            ];
        }

        $steps = [];
        $cursor = $currentMajor;

        while ($cursor < $targetMajor) {
            $next = $cursor + 1;
            $identifier = sprintf('%d-to-%d', $cursor, $next);

            $steps[] = [
                'identifier' => $identifier,
                'guide' => $this->knowledgeBase->getUpgradePath($identifier),
            ];

            $cursor = $next;
        }

        return [
            'upgrade_path' => [
                'identifier' => $pathIdentifier,
                'current' => $currentConstraint,
                'target' => sprintf('%d.x', $targetMajor),
                'steps' => $steps,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function checkPhpCompatibility(ProjectContext $context, string $targetVersion): array
    {
        $composer = ComposerInspector::fromPath($context->getRootPath());
        $phpConstraint = $composer->getPhpConstraint();

        $slug = $this->knowledgeBase->resolveBreakingChangeSlug(sprintf('%d.x', $this->extractMajorVersion($targetVersion)));
        $document = $this->knowledgeBase->getBreakingChangeDocument($slug);

        $requirements = $document['php_requirement'] ?? [];

        $isCompatible = true;
        $warnings = [];

        if (isset($requirements['minimum']) && $phpConstraint !== null) {
            $isCompatible = version_compare($this->normalizeConstraint($phpConstraint), $requirements['minimum'], '>=');

            if (!$isCompatible) {
                $warnings[] = sprintf('PHP constraint %s does not satisfy minimum %s.', $phpConstraint, $requirements['minimum']);
            }
        }

        return [
            'php_constraint' => $phpConstraint,
            'requirements' => $requirements,
            'is_compatible' => $isCompatible,
            'compatible' => $isCompatible,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function analyzeDependencies(ProjectContext $context): array
    {
        $composer = ComposerInspector::fromPath($context->getRootPath());

        $packages = $composer->getRequiredPackages();
        $firstParty = [];
        $thirdParty = [];

        foreach ($packages as $package => $constraint) {
            if (str_starts_with($package, 'laravel/') || str_starts_with($package, 'illuminate/')) {
                $firstParty[$package] = $constraint;
            } else {
                $thirdParty[$package] = $constraint;
            }
        }

        return [
            'first_party' => $firstParty,
            'third_party' => $thirdParty,
            'dev_packages' => $composer->getDevPackages(),
        ];
    }

    private function normalizeConstraint(string $constraint): string
    {
        if (preg_match('/\d+\.\d+(\.\d+)?/', $constraint, $matches) === 1) {
            return $matches[0];
        }

        return $constraint;
    }

    private function extractMajorVersion(string $input): int
    {
        if (preg_match('/(\d{1,2})/', $input, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function determineNextSupportedMajor(int $currentMajor): int
    {
        $identifiers = $this->knowledgeBase->listUpgradePathIdentifiers();
        $candidates = [];

        foreach ($identifiers as $identifier) {
            if (preg_match('/^(\d+)-to-(\d+)$/', $identifier, $matches) !== 1) {
                continue;
            }

            $from = (int) $matches[1];
            $to = (int) $matches[2];

            if ($from === $currentMajor && $to > $currentMajor) {
                $candidates[] = $to;
            }
        }

        if ($candidates === []) {
            return $currentMajor;
        }

        return min($candidates);
    }
}
