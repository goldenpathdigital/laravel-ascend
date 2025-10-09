<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools;

abstract class AbstractTool implements ToolInterface
{
    protected string $schemaVersion = '1.0.0';

    public function getSchemaVersion(): string
    {
        return $this->schemaVersion;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $warnings
     *
     * @return array<string, mixed>
     */
    protected function success(array $data, array $warnings = [], float $startedAt = null): array
    {
        $startedAt ??= microtime(true);

        return [
            'schema_version' => $this->getSchemaVersion(),
            'ok' => true,
            'data' => $data,
            'warnings' => $warnings,
            'timings' => [
                'ms' => $this->calculateElapsedMilliseconds($startedAt),
            ],
        ];
    }

    /**
     * @param array<int, string> $warnings
     *
     * @return array<string, mixed>
     */
    protected function error(string $message, array $warnings = [], float $startedAt = null, ?string $code = null): array
    {
        $startedAt ??= microtime(true);

        return [
            'schema_version' => $this->getSchemaVersion(),
            'ok' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
            ],
            'warnings' => $warnings,
            'timings' => [
                'ms' => $this->calculateElapsedMilliseconds($startedAt),
            ],
        ];
    }

    private function calculateElapsedMilliseconds(float $startedAt): float
    {
        return round((microtime(true) - $startedAt) * 1000, 3);
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
        ];
    }

    public function getAnnotations(): array
    {
        return [];
    }
}
