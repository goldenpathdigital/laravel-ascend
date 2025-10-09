<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Documentation;

use RuntimeException;

final class DocumentationException extends RuntimeException
{
    public static function becauseFileIsMissing(string $path): self
    {
        return new self(sprintf('Knowledge base file not found: %s', $path));
    }

    public static function becauseFileCouldNotBeParsed(string $path, string $error): self
    {
        return new self(sprintf('Unable to parse knowledge base file %s: %s', $path, $error));
    }

    public static function becauseBasePathIsInvalid(string $path): self
    {
        return new self(sprintf('Knowledge base directory is invalid or not accessible: %s', $path));
    }

    public static function becauseDocumentNotFound(string $type, string $identifier): self
    {
        return new self(sprintf('Knowledge base %s "%s" was not found.', $type, $identifier));
    }
}
