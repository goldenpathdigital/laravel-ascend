<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Server\Mcp\Config\TomlFileWriter;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/ascend-test-' . bin2hex(random_bytes(8));
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        array_map('unlink', glob($this->tempDir . '/*'));
        rmdir($this->tempDir);
    }
});

test('toml writer creates new config file with server entry', function () {
    $configPath = $this->tempDir . '/config.toml';

    $writer = new TomlFileWriter($configPath);
    $writer->addServer('test-server', 'php', ['/path/to/artisan', 'serve']);
    $path = $writer->save();

    expect($path)->toBe($configPath);
    expect(file_exists($configPath))->toBeTrue();

    $content = file_get_contents($configPath);
    expect($content)->toContain('# MCP Servers Configuration');
    expect($content)->toContain('[mcp.servers.test-server]');
    expect($content)->toContain('command = "php"');
    expect($content)->toContain('"/path/to/artisan"');
    expect($content)->toContain('"serve"');
});

test('toml writer preserves existing content', function () {
    $configPath = $this->tempDir . '/config.toml';

    // Create initial content
    file_put_contents($configPath, 'model = "gpt-4"' . "\n" . 'temperature = 0.7' . "\n");

    $writer = new TomlFileWriter($configPath);
    $writer->addServer('laravel-ascend', 'php', ['/app/artisan', 'ascend:mcp']);
    $writer->save();

    $content = file_get_contents($configPath);
    
    // Should preserve original content
    expect($content)->toContain('model = "gpt-4"');
    expect($content)->toContain('temperature = 0.7');
    
    // Should add MCP servers
    expect($content)->toContain('# MCP Servers Configuration');
    expect($content)->toContain('[mcp.servers.laravel-ascend]');
    expect($content)->toContain('command = "php"');
});

test('toml writer replaces existing MCP servers section', function () {
    $configPath = $this->tempDir . '/config.toml';

    // Create initial content with MCP servers
    $initial = <<<'TOML'
model = "gpt-4"

# MCP Servers Configuration
[mcp.servers.old-server]
command = "old"
args = [
  "old-arg",
]

TOML;

    file_put_contents($configPath, $initial);

    $writer = new TomlFileWriter($configPath);
    $writer->addServer('new-server', 'php', ['/new/path']);
    $writer->save();

    $content = file_get_contents($configPath);
    
    // Should preserve model
    expect($content)->toContain('model = "gpt-4"');
    
    // Should have new server
    expect($content)->toContain('[mcp.servers.new-server]');
    expect($content)->toContain('command = "php"');
    
    // Should NOT have old server
    expect($content)->not->toContain('old-server');
    expect($content)->not->toContain('old-arg');
});

test('toml writer rejects non-toml files', function () {
    $jsonPath = $this->tempDir . '/config.json';
    
    expect(fn () => new TomlFileWriter($jsonPath))
        ->toThrow(RuntimeException::class, 'must point to a .toml file');
});

test('toml writer rejects path traversal', function () {
    $badPath = $this->tempDir . '/../../../etc/config.toml';
    
    expect(fn () => new TomlFileWriter($badPath))
        ->toThrow(RuntimeException::class, 'path traversal detected');
});

test('toml writer creates directory if not exists', function () {
    $nestedPath = $this->tempDir . '/nested/deep/config.toml';

    $writer = new TomlFileWriter($nestedPath);
    $writer->addServer('test', 'php', ['artisan']);
    $writer->save();

    expect(file_exists($nestedPath))->toBeTrue();
    
    // Cleanup nested dirs
    @unlink($nestedPath);
    @rmdir(dirname($nestedPath));
    @rmdir(dirname(dirname($nestedPath)));
});

test('toml writer handles server with no args', function () {
    $configPath = $this->tempDir . '/config.toml';

    $writer = new TomlFileWriter($configPath);
    $writer->addServer('simple-server', 'node');
    $writer->save();

    $content = file_get_contents($configPath);
    expect($content)->toContain('[mcp.servers.simple-server]');
    expect($content)->toContain('command = "node"');
    expect($content)->not->toContain('args = [');
});
