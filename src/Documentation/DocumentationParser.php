<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Documentation;

final class DocumentationParser
{
    /**
     * @return array<string, mixed>
     */
    public function parseJsonFile(string $path): array
    {
        if (!is_file($path)) {
            throw DocumentationException::becauseFileIsMissing($path);
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw DocumentationException::becauseFileCouldNotBeParsed($path, 'file_get_contents returned false');
        }

        try {
            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw DocumentationException::becauseFileCouldNotBeParsed($path, $exception->getMessage());
        }

        if ($decoded === null) {
            throw DocumentationException::becauseFileCouldNotBeParsed($path, 'decoded data is null');
        }

        return $decoded;
    }
}
