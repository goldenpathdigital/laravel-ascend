<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Server\Mcp\Config;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

final class FileWriter
{
    /** @var string */
    private $filePath;

    /** @var string */
    private $configKey;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $servers = [];

    private LoggerInterface $logger;

    /**
     * @param string $filePath Path to MCP configuration file
     * @param string $configKey Configuration key to modify (default: 'servers')
     * @param LoggerInterface|null $logger Optional logger for security events
     * @throws RuntimeException If file path contains path traversal or is not a JSON file
     */
    public function __construct(
        string $filePath,
        string $configKey = 'servers',
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->validateFilePath($filePath);
        $this->filePath = $filePath;
        $this->configKey = $configKey;

        $this->logger->debug('FileWriter initialized', [
            'file_path' => $filePath,
            'config_key' => $configKey,
        ]);
    }

    /**
     * @param array<int, string>       $args
     * @param array<string, string>    $env
     */
    public function addServer(string $key, string $command, array $args = [], array $env = []): self
    {
        $this->servers[$key] = array_filter([
            'command' => $command,
            'args' => $args,
            'env' => $env,
        ]);

        return $this;
    }

    /**
     * Persist the server configuration to disk.
     */
    public function save(): string
    {
        $this->ensureDirectoryExists();

        if (!is_file($this->filePath) || trim((string) file_get_contents($this->filePath)) === '') {
            $payload = [
                $this->configKey => $this->servers,
            ];

            $this->writeJson($payload);

            return $this->filePath;
        }

        $contents = file_get_contents($this->filePath);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read MCP configuration: %s', $this->filePath));
        }

        $decoded = json_decode($contents, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $decoded[$this->configKey] = array_merge(
                $decoded[$this->configKey] ?? [],
                $this->servers,
            );

            $this->writeJson($decoded);

            return $this->filePath;
        }

        $updated = $this->injectIntoJson5($contents);

        $this->writeRaw($updated);

        return $this->filePath;
    }

    private function ensureDirectoryExists(): void
    {
        $dir = dirname($this->filePath);

        if (is_dir($dir)) {
            return;
        }

        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Unable to create directory for MCP configuration: %s', $dir));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeJson(array $payload): void
    {
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        $this->writeRaw($encoded);
    }

    private function writeRaw(string $contents): void
    {
        if (file_put_contents($this->filePath, $contents) === false) {
            throw new RuntimeException(sprintf('Failed to write MCP configuration: %s', $this->filePath));
        }
    }

    private function injectIntoJson5(string $contents): string
    {
        $pattern = '/["\']' . preg_quote($this->configKey, '/') . '["\']\s*:\s*\{/m';

        if (preg_match($pattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
            [$match, $offset] = $matches[0];
            $openBrace = strpos($contents, '{', $offset);

            if ($openBrace === false) {
                return $this->appendConfigSection($contents);
            }

            $closeBrace = $this->findMatchingBrace($contents, $openBrace);

            if ($closeBrace === null) {
                return $this->appendConfigSection($contents);
            }

            $existingSection = substr($contents, $openBrace + 1, $closeBrace - $openBrace - 1);
            $existingSection = rtrim($existingSection);

            $injection = $this->buildServersJson($this->detectIndentation($contents, $openBrace));

            if ($existingSection === '') {
                return substr($contents, 0, $openBrace + 1)
                    . PHP_EOL
                    . $injection
                    . PHP_EOL
                    . substr($contents, $closeBrace);
            }

            $needsComma = !preg_match('/,\s*$/', substr($contents, 0, $closeBrace - 1));

            if ($needsComma && trim(substr($contents, $openBrace + 1, $closeBrace - $openBrace - 1)) !== '') {
                $contents = substr_replace($contents, ',', $closeBrace - 1, 0);
                $closeBrace++;
            }

            return substr($contents, 0, $closeBrace)
                . PHP_EOL
                . $injection
                . PHP_EOL
                . substr($contents, $closeBrace);
        }

        return $this->appendConfigSection($contents);
    }

    private function appendConfigSection(string $contents): string
    {
        $injection = '"' . $this->configKey . '": {' . PHP_EOL
            . $this->buildServersJson(4) . PHP_EOL . '}' . PHP_EOL;

        $position = strpos($contents, '{');

        if ($position === false) {
            return '{' . PHP_EOL . $injection . '}' . PHP_EOL;
        }

        return substr_replace($contents, $injection, $position + 1, 0);
    }

    private function buildServersJson(int $indentSize): string
    {
        $indent = str_repeat(' ', $indentSize);

        $segments = [];

        foreach ($this->servers as $key => $config) {
            $encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $encoded = str_replace("\r\n", "\n", (string) $encoded);
            $lines = explode("\n", $encoded);
            $first = array_shift($lines);
            $segments[] = $indent . '"' . $key . '": ' . $first;
            foreach ($lines as $line) {
                $segments[] = $indent . str_repeat(' ', 2) . $line;
            }
        }

        return implode(',' . PHP_EOL, $segments);
    }

    private function detectIndentation(string $contents, int $position): int
    {
        $lineStart = strrpos(substr($contents, 0, $position), PHP_EOL);

        if ($lineStart === false) {
            return 4;
        }

        $line = substr($contents, $lineStart + 1, $position - $lineStart - 1);
        $spaces = strlen($line) - strlen(ltrim($line, " \t"));

        return $spaces > 0 ? $spaces : 4;
    }

    private function findMatchingBrace(string $contents, int $openPosition): ?int
    {
        $depth = 0;
        $length = strlen($contents);

        for ($i = $openPosition; $i < $length; $i++) {
            $char = $contents[$i];

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * Validate that the file path is safe and appropriate for MCP configuration.
     *
     * @throws RuntimeException If path is invalid
     */
    private function validateFilePath(string $filePath): void
    {
        // Check for path traversal attempts
        if (str_contains($filePath, '..')) {
            $this->logger->warning('Path traversal attempt in FileWriter', [
                'file_path' => $filePath,
                'violation' => 'contains_dotdot',
            ]);
            throw new RuntimeException('File path cannot contain ".." (path traversal detected)');
        }

        // Ensure it's a JSON file
        if (!str_ends_with($filePath, '.json')) {
            $this->logger->warning('Invalid file extension in FileWriter', [
                'file_path' => $filePath,
                'violation' => 'not_json',
            ]);
            throw new RuntimeException('File path must point to a .json file');
        }

        // Extract filename to check for common MCP config names
        $fileName = basename($filePath);
        $validNames = ['mcp.json', 'cline_mcp_settings.json'];

        if (!in_array($fileName, $validNames, true)) {
            // Allow other names but ensure they contain 'mcp' for safety
            if (!str_contains(strtolower($fileName), 'mcp')) {
                $this->logger->warning('Suspicious file name in FileWriter', [
                    'file_name' => $fileName,
                    'violation' => 'not_mcp_config',
                ]);
                throw new RuntimeException(sprintf(
                    'File name "%s" does not appear to be an MCP configuration file',
                    $fileName,
                ));
            }
        }

        $this->logger->debug('File path validated successfully', ['file_name' => $fileName]);
    }
}
