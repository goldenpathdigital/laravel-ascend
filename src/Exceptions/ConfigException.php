<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Exceptions;

use RuntimeException;

class ConfigException extends RuntimeException
{
    public static function fileNotFound(string $path): self
    {
        return new self(sprintf('Configuration file not found: %s', $path));
    }

    public static function invalidKey(string $key): self
    {
        return new self(sprintf('Invalid configuration key: %s', $key));
    }

    public static function loadFailed(string $reason): self
    {
        return new self(sprintf('Failed to load configuration: %s', $reason));
    }
}
