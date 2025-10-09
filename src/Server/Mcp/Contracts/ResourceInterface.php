<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts;

interface ResourceInterface
{
    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
