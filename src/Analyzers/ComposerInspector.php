<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Analyzers;

final class ComposerInspector
{
    /**
     * @var array<string, mixed>
     */
    private array $composerData;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $lockData;

    /**
     * @param array<string, mixed> $composerData
     * @param array<string, mixed>|null $lockData
     */
    private function __construct(array $composerData, ?array $lockData)
    {
        $this->composerData = $composerData;
        $this->lockData = $lockData;
    }

    public static function fromPath(string $projectRoot): self
    {
        $composerPath = $projectRoot . '/composer.json';
        $lockPath = $projectRoot . '/composer.lock';

        $composerData = self::decodeFile($composerPath);
        $lockData = is_file($lockPath) ? self::decodeFile($lockPath) : null;

        return new self($composerData, $lockData);
    }

    public function getPhpConstraint(): ?string
    {
        return $this->composerData['require']['php'] ?? null;
    }

    public function getLaravelFrameworkConstraint(): ?string
    {
        return $this->composerData['require']['laravel/framework'] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getRequiredPackages(): array
    {
        $packages = [];

        foreach (($this->composerData['require'] ?? []) as $package => $constraint) {
            if ($package === 'php') {
                continue;
            }

            $packages[$package] = (string) $constraint;
        }

        return $packages;
    }

    /**
     * @return array<string, string>
     */
    public function getDevPackages(): array
    {
        $packages = [];

        foreach (($this->composerData['require-dev'] ?? []) as $package => $constraint) {
            $packages[$package] = (string) $constraint;
        }

        return $packages;
    }

    /**
     * @return array<string, string>
     */
    public function getInstalledVersions(): array
    {
        $installed = [];

        if ($this->lockData === null) {
            return $installed;
        }

        foreach (['packages', 'packages-dev'] as $section) {
            foreach (($this->lockData[$section] ?? []) as $package) {
                if (!isset($package['name'], $package['version'])) {
                    continue;
                }

                $installed[$package['name']] = (string) $package['version'];
            }
        }

        return $installed;
    }

    /**
     * @return array<string, mixed>
     */
    public function getComposerData(): array
    {
        return $this->composerData;
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                return [];
            }

            return $decoded;
        } catch (\JsonException) {
            return [];
        }
    }
}
