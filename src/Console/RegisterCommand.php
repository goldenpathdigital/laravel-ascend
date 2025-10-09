<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Console;

use GoldenPathDigital\LaravelAscend\Server\Mcp\McpRegistration;
use Illuminate\Console\Command;

class RegisterCommand extends Command
{
    protected $signature = 'ascend:register {--global : Force registration to user-level targets only}';

    protected $description = 'Register the Ascend MCP server with supported IDE configuration files.';

    public function handle(): int
    {
        $artisanPath = $this->resolveArtisanPath();
        $registration = new McpRegistration($artisanPath);

        $projectRoot = $this->option('global') ? null : base_path();

        $writtenPaths = $registration->register($projectRoot);

        if ($writtenPaths === []) {
            $this->warn('No MCP configuration targets detected. Set VSCODE_MCP_CONFIG to override.');

            return self::SUCCESS;
        }

        foreach ($writtenPaths as $path) {
            $this->info('Registered Ascend MCP server in ' . $path);
        }

        return self::SUCCESS;
    }

    private function resolveArtisanPath(): string
    {
        $artisan = base_path('artisan');

        if (!is_file($artisan)) {
            throw new \RuntimeException('Unable to resolve the artisan script. Is this a Laravel application?');
        }

        return realpath($artisan) ?: $artisan;
    }
}
