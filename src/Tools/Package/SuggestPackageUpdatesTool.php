<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools\Package;

use GoldenPathDigital\LaravelAscend\Analyzers\ComposerInspector;
use GoldenPathDigital\LaravelAscend\Tools\ProjectAwareTool;

final class SuggestPackageUpdatesTool extends ProjectAwareTool
{
    public function getName(): string
    {
        return 'suggest_package_updates';
    }

    public function getDescription(): string
    {
        return 'Provide suggestions for updating Composer packages ahead of a Laravel upgrade.';
    }

    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        $context = $this->createContext($payload);
        $target = isset($payload['target']) ? (string) $payload['target'] : sprintf('%d.x', $this->determineLatestMajor());

        $composer = ComposerInspector::fromPath($context->getRootPath());

        $suggestions = [];

        $targetMajor = $this->extractMajorVersion($target);

        foreach ($composer->getRequiredPackages() as $package => $constraint) {
            $installed = $composer->getInstalledVersions()[$package] ?? null;

            if ($this->isFirstParty($package)) {
                $major = $this->extractMajorVersion($constraint);

                if ($major < $targetMajor) {
                    $suggestions[] = [
                        'package' => $package,
                        'current_constraint' => $constraint,
                        'installed_version' => $installed,
                        'suggestion' => sprintf('Update to support Laravel %s (current major %d)', $target, $targetMajor),
                    ];
                }
            }

            if (!str_starts_with($constraint, '^') && !str_starts_with($constraint, '~')) {
                $suggestions[] = [
                    'package' => $package,
                    'current_constraint' => $constraint,
                    'installed_version' => $installed,
                    'suggestion' => 'Consider adopting caret/tilde constraints for easier upgrades.',
                ];
            }
        }

        return $this->success(
            data: [
                'target' => $target,
                'suggestions' => $suggestions,
            ],
            startedAt: $startedAt,
        );
    }

    private function isFirstParty(string $package): bool
    {
        return str_starts_with($package, 'laravel/') || str_starts_with($package, 'illuminate/');
    }

    private function extractMajorVersion(string $input): int
    {
        if (preg_match('/(\d{1,2})/', $input, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function determineLatestMajor(): int
    {
        $versions = $this->knowledgeBase->listBreakingChangeIdentifiers();

        if ($versions === []) {
            return 0;
        }

        // Extract major version numbers from identifiers like "laravel-11"
        $majors = [];
        foreach ($versions as $identifier) {
            if (preg_match('/laravel-(\d+)/', $identifier, $matches)) {
                $majors[] = (int) $matches[1];
            }
        }

        return $majors !== [] ? max($majors) : 0;
    }
}
