<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Package;

use GoldenPathDigital\LaravelAscend\Analyzers\ComposerInspector;
use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class CheckPackageCompatibilityTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'check_package_compatibility';
    }

    public function getDescription(): string
    {
        return 'Check whether a Composer package constraint is compatible with a target Laravel version.';
    }

    public function getInputSchema(): array
    {
        return $this->buildSchema(
            array_merge(
                $this->baseProjectProperties(),
                [
                    'package' => [
                        'type' => 'string',
                        'description' => 'Composer package name to inspect (e.g. "laravel/framework").',
                    ],
                    'target' => [
                        'type' => 'string',
                        'description' => 'Target Laravel version to validate against (e.g. "11.x").',
                    ],
                ]
            ),
            ['package', 'target']
        );
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);

        $package = isset($payload['package']) ? (string) $payload['package'] : '';
        $target = isset($payload['target']) ? (string) $payload['target'] : '';

        if ($package === '' || $target === '') {
            return $this->error('Parameters "package" and "target" are required.', [], $startedAt, 'invalid_request');
        }

        $composer = ComposerInspector::fromPath($context->getRootPath());
        $constraint = $composer->getRequiredPackages()[$package] ?? $composer->getDevPackages()[$package] ?? null;
        $installed = $composer->getInstalledVersions()[$package] ?? null;

        $compatible = true;
        $warnings = [];
        $recommendations = [];

        if ($constraint === null) {
            $warnings[] = sprintf('Package %s is not required in composer.json.', $package);
            $compatible = false;
        } elseif ($package === 'laravel/framework') {
            // Layer 1: Laravel Framework Check
            $result = $this->checkFrameworkCompatibility($constraint, $target);
            $compatible = $result['compatible'];
            $warnings = array_merge($warnings, $result['warnings']);
            $recommendations = array_merge($recommendations, $result['recommendations']);
        } elseif (str_starts_with($package, 'laravel/')) {
            // Layer 2: First-Party Laravel Packages
            $result = $this->checkFirstPartyPackage($package, $constraint, $target);
            $compatible = $result['compatible'];
            $warnings = array_merge($warnings, $result['warnings']);
            $recommendations = array_merge($recommendations, $result['recommendations']);
        } else {
            // Layer 3: Third-Party Packages
            $result = $this->checkThirdPartyPackage($package, $constraint, $target);
            $compatible = $result['compatible'];
            $warnings = array_merge($warnings, $result['warnings']);
            $recommendations = array_merge($recommendations, $result['recommendations']);
        }

        return $this->success(
            [
                'package' => $package,
                'constraint' => $constraint,
                'installed_version' => $installed,
                'target' => $target,
                'compatible' => $compatible,
                'warnings' => $warnings,
                'recommendations' => $recommendations,
            ],
            $warnings,
            $startedAt
        );
    }

    /**
     * @return array{compatible: bool, warnings: array<string>, recommendations: array<string>}
     */
    private function checkFrameworkCompatibility(string $constraint, string $target): array
    {
        $targetMajor = $this->extractMajorVersion($target);
        $constraintMajor = $this->extractMajorVersion($constraint);

        $compatible = $constraintMajor >= $targetMajor;
        $warnings = [];
        $recommendations = [];

        if (!$compatible) {
            $warnings[] = sprintf('Constraint %s does not satisfy target Laravel %s.', $constraint, $target);
            $recommendations[] = sprintf('Update to ^%d.0', $targetMajor);
        }

        return [
            'compatible' => $compatible,
            'warnings' => $warnings,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @return array{compatible: bool|null, warnings: array<string>, recommendations: array<string>}
     */
    private function checkThirdPartyPackage(string $package, string $constraint, string $target): array
    {
        $targetMajor = $this->extractMajorVersion($target);
        $constraintMajor = $this->extractMajorVersion($constraint);

        $warnings = [];
        $recommendations = [];
        $compatible = null; // null = unknown, needs manual verification

        // Detect very old packages (major version < 5 for Laravel 10+)
        if ($targetMajor >= 10 && $constraintMajor > 0 && $constraintMajor < 5) {
            $warnings[] = sprintf('Package %s constraint %s appears very old for Laravel %d.', $package, $constraint, $targetMajor);
            $recommendations[] = 'Check package documentation for Laravel ' . $targetMajor . ' compatibility';
            $compatible = false;
        }

        // Warn about exact version constraints
        if (preg_match('/^\d+\.\d+\.\d+$/', trim($constraint)) === 1) {
            $warnings[] = sprintf('Exact version constraint %s may prevent upgrades.', $constraint);
            $recommendations[] = 'Consider using a caret (^) or tilde (~) constraint';
        }

        // Warn about dev constraints
        if (str_contains($constraint, 'dev-') || str_contains($constraint, '@dev')) {
            $warnings[] = 'Development constraint detected - verify stability for production.';
            $recommendations[] = 'Use a stable version constraint';
        }

        // If no issues detected, recommend manual verification
        if ($compatible === null && $warnings === []) {
            $recommendations[] = sprintf('Manually verify %s compatibility with Laravel %d', $package, $targetMajor);
        }

        return [
            'compatible' => $compatible,
            'warnings' => $warnings,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @return array{compatible: bool|null, warnings: array<string>, recommendations: array<string>}
     */
    private function checkFirstPartyPackage(string $package, string $constraint, string $target): array
    {
        $targetMajor = $this->extractMajorVersion($target);
        $constraintMajor = $this->extractMajorVersion($constraint);

        $warnings = [];
        $recommendations = [];

        // Version mapping database for common Laravel packages
        $versionMap = $this->getFirstPartyVersionMap();

        if (isset($versionMap[$package][$targetMajor])) {
            $requiredMajor = $versionMap[$package][$targetMajor];
            $compatible = $constraintMajor >= $requiredMajor;

            if (!$compatible) {
                $warnings[] = sprintf(
                    'Laravel %d requires %s ^%d.0, but constraint is %s.',
                    $targetMajor,
                    $package,
                    $requiredMajor,
                    $constraint
                );
                $recommendations[] = sprintf('Update to ^%d.0', $requiredMajor);
            }

            return [
                'compatible' => $compatible,
                'warnings' => $warnings,
                'recommendations' => $recommendations,
            ];
        }

        // Smart heuristics for packages without explicit mapping
        $compatible = null;

        // If the package version is very old (e.g., v2.x for Laravel 12), flag it
        if ($targetMajor >= 10 && $constraintMajor > 0 && $constraintMajor < 3) {
            $warnings[] = sprintf(
                'Package %s constraint %s appears outdated for Laravel %d.',
                $package,
                $constraint,
                $targetMajor
            );
            $recommendations[] = 'Check Laravel documentation for recommended version';
            $compatible = false;
        }

        // Package version typically aligns with Laravel version for many first-party packages
        if ($constraintMajor > 0 && $constraintMajor < $targetMajor - 2) {
            $warnings[] = sprintf(
                'Package %s major version %d is significantly behind Laravel %d.',
                $package,
                $constraintMajor,
                $targetMajor
            );
            $recommendations[] = sprintf('Consider upgrading to a version closer to Laravel %d', $targetMajor);
            $compatible = false;
        }

        if ($compatible === null && $warnings === []) {
            $recommendations[] = sprintf('Manually verify %s compatibility with Laravel %d', $package, $targetMajor);
        }

        return [
            'compatible' => $compatible,
            'warnings' => $warnings,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function getFirstPartyVersionMap(): array
    {
        return [
            'laravel/sanctum' => [
                12 => 4,
                11 => 4,
                10 => 3,
                9 => 3,
                8 => 2,
            ],
            'laravel/passport' => [
                12 => 12,
                11 => 12,
                10 => 11,
                9 => 11,
                8 => 10,
            ],
            'laravel/horizon' => [
                12 => 5,
                11 => 5,
                10 => 5,
                9 => 5,
                8 => 5,
            ],
            'laravel/telescope' => [
                12 => 5,
                11 => 5,
                10 => 4,
                9 => 4,
                8 => 4,
            ],
            'laravel/scout' => [
                12 => 10,
                11 => 10,
                10 => 10,
                9 => 9,
                8 => 9,
            ],
            'laravel/cashier' => [
                12 => 15,
                11 => 15,
                10 => 14,
                9 => 14,
                8 => 13,
            ],
            'laravel/breeze' => [
                12 => 2,
                11 => 2,
                10 => 1,
                9 => 1,
            ],
            'laravel/jetstream' => [
                12 => 5,
                11 => 5,
                10 => 4,
                9 => 3,
                8 => 2,
            ],
            'laravel/sail' => [
                12 => 1,
                11 => 1,
                10 => 1,
                9 => 1,
                8 => 1,
            ],
            'laravel/socialite' => [
                12 => 5,
                11 => 5,
                10 => 5,
                9 => 5,
                8 => 5,
            ],
        ];
    }

    private function extractMajorVersion(string $input): int
    {
        if (preg_match('/(\d{1,2})/', $input, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }
}
