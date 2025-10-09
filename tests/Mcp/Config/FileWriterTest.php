<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Server\Mcp\Config\FileWriter;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/ascend-test-' . uniqid();
    mkdir($this->tempDir, 0755, true);
    $this->configPath = $this->tempDir . '/mcp.json';
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($this->tempDir);
    }
});

test('file writer creates new config file with server entry', function () {
    $writer = new FileWriter($this->configPath);
    $writer->addServer('test-server', 'php', ['artisan', 'serve']);

    $path = $writer->save();

    expect($path)->toBe($this->configPath);
    expect(file_exists($this->configPath))->toBeTrue();

    $content = json_decode(file_get_contents($this->configPath), true);
    expect($content)->toHaveKey('servers');
    expect($content['servers'])->toHaveKey('test-server');
    expect($content['servers']['test-server'])->toMatchArray([
        'command' => 'php',
        'args' => ['artisan', 'serve'],
    ]);
});

test('file writer merges with existing config', function () {
    // Create initial config
    $initialConfig = [
        'servers' => [
            'existing-server' => [
                'command' => 'node',
                'args' => ['server.js'],
            ],
        ],
    ];
    file_put_contents($this->configPath, json_encode($initialConfig, JSON_PRETTY_PRINT));

    $writer = new FileWriter($this->configPath);
    $writer->addServer('new-server', 'python', ['run.py']);
    $writer->save();

    $content = json_decode(file_get_contents($this->configPath), true);
    expect($content['servers'])->toHaveKey('existing-server');
    expect($content['servers'])->toHaveKey('new-server');
});

test('file writer can add multiple servers', function () {
    $writer = new FileWriter($this->configPath);
    $writer->addServer('server-1', 'cmd1', ['arg1']);
    $writer->addServer('server-2', 'cmd2', ['arg2']);

    $writer->save();

    $content = json_decode(file_get_contents($this->configPath), true);
    expect($content['servers'])->toHaveCount(2);
    expect($content['servers'])->toHaveKeys(['server-1', 'server-2']);
});

test('file writer supports environment variables', function () {
    $writer = new FileWriter($this->configPath);
    $writer->addServer('test-server', 'php', ['artisan'], ['ENV_VAR' => 'value']);

    $writer->save();

    $content = json_decode(file_get_contents($this->configPath), true);
    expect($content['servers']['test-server'])->toHaveKey('env');
    expect($content['servers']['test-server']['env'])->toBe(['ENV_VAR' => 'value']);
});

test('file writer creates directory if not exists', function () {
    $deepPath = $this->tempDir . '/nested/deep/mcp.json';

    $writer = new FileWriter($deepPath);
    $writer->addServer('test', 'cmd');
    $writer->save();

    expect(file_exists($deepPath))->toBeTrue();
});

test('file writer uses custom config key', function () {
    $writer = new FileWriter($this->configPath, 'mcpServers');
    $writer->addServer('test', 'cmd');
    $writer->save();

    $content = json_decode(file_get_contents($this->configPath), true);
    expect($content)->toHaveKey('mcpServers');
    expect($content)->not->toHaveKey('servers');
});

test('file writer creates directories with secure permissions', function () {
    $deepPath = $this->tempDir . '/secure/mcp.json';

    $writer = new FileWriter($deepPath);
    $writer->addServer('test', 'cmd');
    $writer->save();

    $dirPerms = fileperms(dirname($deepPath)) & 0777;
    expect($dirPerms)->toBe(0755);
});
