<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools;

interface ToolInterface
{
    public function getName(): string;

    public function getSchemaVersion(): string;

    public function getDescription(): string;

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function execute(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function getInputSchema(): array;

    /**
     * @return array<string, mixed>
     */
    public function getAnnotations(): array;
}
