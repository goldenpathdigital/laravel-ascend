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

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);

        $package = isset($payload['package']) ? (string) $payload['package'] : '';
        $target = isset($payload['target']) ? (string) $payload['target'] : '';

        if ($package === '' || $target === '') {
            return $this->error('Parameters "package" and "target" are required.', startedAt: $startedAt, code: 'invalid_request');
        }

        $composer = ComposerInspector::fromPath($context->getRootPath());
        $constraint = $composer->getRequiredPackages()[$package] ?? $composer->getDevPackages()[$package] ?? null;
        $installed = $composer->getInstalledVersions()[$package] ?? null;

        $compatible = true;
        $warnings = [];

        if ($constraint === null) {
            $warnings[] = sprintf('Package %s is not required in composer.json.', $package);
            $compatible = false;
        } elseif ($package === 'laravel/framework') {
            $targetMajor = $this->extractMajorVersion($target);
            $constraintMajor = $this->extractMajorVersion($constraint);

            $compatible = $constraintMajor >= $targetMajor;

            if (!$compatible) {
                $warnings[] = sprintf('Constraint %s does not satisfy target Laravel %s.', $constraint, $target);
            }
        }

        return $this->success(
            data: [
                'package' => $package,
                'constraint' => $constraint,
                'installed_version' => $installed,
                'target' => $target,
                'compatible' => $compatible,
            ],
            warnings: $warnings,
            startedAt: $startedAt,
        );
    }

    private function extractMajorVersion(string $input): int
    {
        if (preg_match('/(\d{1,2})/', $input, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }
}
