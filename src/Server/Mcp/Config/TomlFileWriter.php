<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Server\Mcp\Config;

use RuntimeException;

final class TomlFileWriter
{
    /** @var string */
    private $filePath;
    
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $servers = [];

    public function __construct(string $filePath)
    {
        $this->validateFilePath($filePath);
        $this->filePath = $filePath;
    }

    /**
     * @param array<int, string> $args
     */
    public function addServer(string $key, string $command, array $args = []): self
    {
        $this->servers[$key] = [
            'command' => $command,
            'args' => $args,
        ];

        return $this;
    }

    public function save(): string
    {
        $this->ensureDirectoryExists();

        $existingContent = '';
        $mcpServersSection = '';
        
        if (is_file($this->filePath)) {
            $existingContent = file_get_contents($this->filePath);
            if ($existingContent === false) {
                throw new RuntimeException(sprintf('Unable to read TOML configuration: %s', $this->filePath));
            }
            
            // Remove existing MCP servers section if it exists
            // Split at the MCP comment and only keep content before it
            $parts = explode('# MCP Servers Configuration', $existingContent);
            $existingContent = trim($parts[0]);
        }

        // Build MCP servers section
        if ($this->servers !== []) {
            $mcpServersSection = "\n\n# MCP Servers Configuration\n";
            
            foreach ($this->servers as $key => $config) {
                $mcpServersSection .= sprintf("[mcp.servers.%s]\n", $key);
                $mcpServersSection .= sprintf('command = "%s"', $config['command']) . "\n";
                
                if (!empty($config['args'])) {
                    $mcpServersSection .= "args = [\n";
                    foreach ($config['args'] as $arg) {
                        $mcpServersSection .= sprintf('  "%s",', addslashes($arg)) . "\n";
                    }
                    $mcpServersSection .= "]\n";
                }
                
                $mcpServersSection .= "\n";
            }
        }

        $finalContent = $existingContent;
        if ($mcpServersSection !== '') {
            if ($existingContent !== '') {
                $finalContent .= "\n" . $mcpServersSection;
            } else {
                $finalContent = trim($mcpServersSection);
            }
        }

        $this->writeRaw($finalContent);

        return $this->filePath;
    }

    private function ensureDirectoryExists(): void
    {
        $dir = dirname($this->filePath);

        if (is_dir($dir)) {
            return;
        }

        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Unable to create directory for TOML configuration: %s', $dir));
        }
    }

    private function writeRaw(string $contents): void
    {
        if (file_put_contents($this->filePath, $contents) === false) {
            throw new RuntimeException(sprintf('Failed to write TOML configuration: %s', $this->filePath));
        }
    }

    private function validateFilePath(string $filePath): void
    {
        if (str_contains($filePath, '..')) {
            throw new RuntimeException('File path cannot contain ".." (path traversal detected)');
        }

        if (!str_ends_with($filePath, '.toml')) {
            throw new RuntimeException('File path must point to a .toml file');
        }
    }
}
