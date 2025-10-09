<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Console;

use GoldenPathDigital\LaravelAscend\Server\Mcp\McpRegistration;
use Illuminate\Console\Command;

class RegisterCommand extends Command
{
    protected $signature = 'ascend:register 
        {--global : Force registration to user-level targets only}
        {--all : Register to all available targets without prompting}';

    protected $description = 'Register the Ascend MCP server with supported IDE configuration files.';

    public function handle(): int
    {
        $artisanPath = $this->resolveArtisanPath();
        $registration = new McpRegistration($artisanPath);

        $projectRoot = $this->option('global') ? null : base_path();
        $allTargets = $registration->determineAvailableTargets($projectRoot);

        if ($allTargets === []) {
            $this->warn('No MCP configuration targets detected. Set VSCODE_MCP_CONFIG to override.');
            return self::SUCCESS;
        }

        $selectedTargets = $this->option('all') 
            ? $allTargets 
            : $this->promptForTargets($allTargets);

        if ($selectedTargets === []) {
            $this->info('No targets selected. Exiting.');
            return self::SUCCESS;
        }

        $writtenPaths = $registration->register($projectRoot, $selectedTargets);

        if ($writtenPaths === []) {
            $this->warn('No configurations were written.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('✓ Successfully registered Ascend MCP server!');
        $this->newLine();
        
        foreach ($writtenPaths as $path) {
            $this->line('  • ' . $path);
        }

        $this->newLine();
        $this->comment('Restart your IDE/agent to load the new configuration.');

        return self::SUCCESS;
    }

    /**
     * @param array<int, array{path:string, configKey:string, format?:string, label:string}> $allTargets
     * @return array<int, array{path:string, configKey:string, format?:string, label:string}>
     */
    private function promptForTargets(array $allTargets): array
    {
        $this->info('Available MCP configuration targets:');
        $this->newLine();

        $choices = [];
        foreach ($allTargets as $index => $target) {
            $choices[] = $target['label'];
        }

        $selected = $this->choice(
            'Select targets to configure (comma-separated numbers, or "all"):',
            array_merge(['all', 'none'], $choices),
            'all',
            null,
            true
        );

        if (in_array('none', $selected, true)) {
            return [];
        }

        if (in_array('all', $selected, true)) {
            return $allTargets;
        }

        $selectedTargets = [];
        foreach ($allTargets as $index => $target) {
            if (in_array($target['label'], $selected, true)) {
                $selectedTargets[] = $target;
            }
        }

        return $selectedTargets;
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
