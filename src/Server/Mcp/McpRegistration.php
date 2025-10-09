<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Server\Mcp;

use GoldenPathDigital\LaravelAscend\Server\Mcp\Config\FileWriter;
use GoldenPathDigital\LaravelAscend\Server\Mcp\Config\TomlFileWriter;

final class McpRegistration
{
    /** @var string */
    private $binaryPath;
    
    public function __construct(
        string $binaryPath
    ) {
        $this->binaryPath = $binaryPath;
    }

    /**
     * @param array<int, array{path:string, configKey:string, format?:string, label?:string}>|null $targets
     *
     * @return array<int, string> paths written
     */
    public function register(?string $projectRoot = null, ?array $targets = null): array
    {
        $projectRoot ??= getcwd() ?: null;
        $targets ??= $this->determineTargets($projectRoot);

        $artisan = $this->resolveConsoleEntryPoint();
        $command = ['php', $artisan, 'ascend:mcp'];

        $written = [];

        foreach ($targets as $target) {
            $format = $target['format'] ?? 'json';
            
            if ($format === 'toml') {
                $writer = new TomlFileWriter($target['path']);
                $writer->addServer('laravel-ascend', $command[0], array_slice($command, 1));
                $written[] = $writer->save();
            } else {
                $writer = new FileWriter($target['path'], $target['configKey']);
                $writer->addServer('laravel-ascend', $command[0], array_slice($command, 1));
                $written[] = $writer->save();
            }
        }

        return array_values(array_unique($written));
    }

    /**
     * Get available targets without registering
     *
     * @return array<int, array{path:string, configKey:string, format?:string, label:string}>
     */
    public function determineAvailableTargets(?string $projectRoot = null): array
    {
        return $this->determineTargets($projectRoot);
    }

    private function resolveConsoleEntryPoint(): string
    {
        if (!is_file($this->binaryPath)) {
            throw new \RuntimeException('Unable to resolve artisan console entry point for MCP registration.');
        }

        return realpath($this->binaryPath) ?: $this->binaryPath;
    }

    /**
     * @return array<int, array{path:string, configKey:string, format?:string, label:string}>
     */
    private function determineTargets(?string $projectRoot): array
    {
        $targets = [];

        $override = getenv('VSCODE_MCP_CONFIG');

        if ($override !== false && $override !== null && $override !== '') {
            $targets[] = [
                'path' => $override,
                'configKey' => 'servers',
                'label' => 'Custom (VSCODE_MCP_CONFIG)',
            ];

            return $targets;
        }

        if ($projectRoot !== null) {
            $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);

            $targets[] = [
                'path' => $projectRoot . '/.vscode/mcp.json',
                'configKey' => 'servers',
                'label' => 'VSCode (Project)',
            ];

            $targets[] = [
                'path' => $projectRoot . '/.cursor/mcp.json',
                'configKey' => 'mcpServers',
                'label' => 'Cursor (Project)',
            ];

            $targets[] = [
                'path' => $projectRoot . '/.junie/mcp/mcp.json',
                'configKey' => 'servers',
                'label' => 'Junie (Project)',
            ];
        }

        $home = $_SERVER['HOME'] ?? getenv('HOME');

        if ($home !== false && $home !== null && $home !== '') {
            $home = rtrim($home, DIRECTORY_SEPARATOR);

            $targets[] = [
                'path' => $home . '/.config/Code/User/mcp.json',
                'configKey' => 'servers',
                'label' => 'VSCode (Global)',
            ];

            $targets[] = [
                'path' => $home . '/.config/Code/User/globalStorage/saoudrizwan.claude-dev/settings/cline_mcp_settings.json',
                'configKey' => 'mcpServers',
                'label' => 'Cline (Global)',
            ];

            $targets[] = [
                'path' => $home . '/.claude/mcp/mcp.json',
                'configKey' => 'servers',
                'label' => 'Claude Desktop (Legacy)',
            ];

            $targets[] = [
                'path' => $home . '/.config/Claude/mcp.json',
                'configKey' => 'servers',
                'label' => 'Claude Desktop',
            ];

            $targets[] = [
                'path' => $home . '/.config/Codex/mcp.json',
                'configKey' => 'servers',
                'label' => 'Codex (JSON)',
            ];
            
            // Codex TOML configuration
            $targets[] = [
                'path' => $home . '/.codex/config.toml',
                'configKey' => 'mcp.servers',
                'format' => 'toml',
                'label' => 'Codex (TOML)',
            ];
        }

        return $targets;
    }
}
