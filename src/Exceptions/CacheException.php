<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Exceptions;

use RuntimeException;

class CacheException extends RuntimeException
{
    public static function invalidKey(string $key): self
    {
        return new self(sprintf(
            'Invalid cache key "%s". Keys must contain only alphanumeric characters, underscores, hyphens, and dots.',
            $key
        ));
    }

    public static function valueTooLarge(string $key, int $size, int $maxSize): self
    {
        return new self(sprintf(
            'Cache value for key "%s" is too large (%d bytes). Maximum allowed size is %d bytes.',
            $key,
            $size,
            $maxSize
        ));
    }
}
