<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Exceptions;

use InvalidArgumentException;

class ToolException extends InvalidArgumentException
{
    public static function notRegistered(string $toolName): self
    {
        return new self(sprintf('Tool "%s" is not registered.', $toolName));
    }

    public static function invalidInput(string $message): self
    {
        return new self(sprintf('Invalid tool input: %s', $message));
    }

    public static function executionFailed(string $toolName, string $reason): self
    {
        return new self(sprintf('Tool "%s" execution failed: %s', $toolName, $reason));
    }
}
