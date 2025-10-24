<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Server\Mcp\McpRegistration;

function createArtisanStub(): string
{
    $path = tempnam(sys_get_temp_dir(), 'artisan-');

    if ($path === false) {
        throw new RuntimeException('Unable to create artisan stub.');
    }

    file_put_contents($path, "<?php\n// stub artisan script\n");

    return $path;
}

function removePath(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    if (is_file($path)) {
        @unlink($path);

        return;
    }

    $items = scandir($path);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        removePath($path . DIRECTORY_SEPARATOR . $item);
    }

    @rmdir($path);
}

it('registers vscode mcp configuration', function (): void {
    $tempDir = sys_get_temp_dir() . '/ascend-mcp-' . uniqid();
    $configPath = $tempDir . '/.vscode/mcp.json';

    try {
        mkdir(dirname($configPath), 0777, true);

        $artisanPath = createArtisanStub();

        $registration = new McpRegistration($artisanPath);
        $written = $registration->register(
            $tempDir,
            [
                ['path' => $configPath, 'configKey' => 'servers'],
            ],
        );

        expect($written)->toContain($configPath);

        $data = json_decode(file_get_contents($configPath), true, 512, JSON_THROW_ON_ERROR);

        expect($data['servers']['laravel-ascend'])
            ->toHaveKey('command', 'php')
            ->and($data['servers']['laravel-ascend']['args'])
            ->toHaveCount(2)
            ->and($data['servers']['laravel-ascend']['args'][0])
            ->toBe($artisanPath)
            ->and($data['servers']['laravel-ascend']['args'][1])
            ->toBe('ascend:mcp');
    } finally {
        if (is_file($configPath)) {
            unlink($configPath);
        }
        if (isset($artisanPath) && is_file($artisanPath)) {
            unlink($artisanPath);
        }
        $dir = dirname($configPath);
        if (is_dir($dir)) {
            @rmdir($dir);
        }
        if (is_dir($tempDir)) {
            @rmdir($tempDir);
        }
    }
});

it('merges existing json configurations without overwriting other servers', function (): void {
    $tempDir = sys_get_temp_dir() . '/ascend-mcp-' . uniqid();
    $configPath = $tempDir . '/mcp.json';

    mkdir($tempDir, 0777, true);

    file_put_contents($configPath, json_encode([
        'servers' => [
            'existing' => [
                'command' => 'node',
                'args' => ['app.js'],
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $artisanPath = createArtisanStub();

    $registration = new McpRegistration($artisanPath);
    $written = $registration->register(null, [
        ['path' => $configPath, 'configKey' => 'servers'],
    ]);

    expect($written)->toContain($configPath);

    $data = json_decode(file_get_contents($configPath), true, 512, JSON_THROW_ON_ERROR);

    expect($data['servers'])->toHaveKeys(['existing', 'laravel-ascend']);

    unlink($configPath);
    rmdir($tempDir);
    if (is_file($artisanPath)) {
        unlink($artisanPath);
    }
});

it('registers project and global targets automatically', function (): void {
    $homeDir = sys_get_temp_dir() . '/ascend-home-' . uniqid();
    $projectDir = sys_get_temp_dir() . '/ascend-project-' . uniqid();

    mkdir($homeDir, 0777, true);
    mkdir($projectDir, 0777, true);

    putenv('HOME=' . $homeDir);
    $_SERVER['HOME'] = $homeDir;
    putenv('VSCODE_MCP_CONFIG');

    $artisanPath = createArtisanStub();

    $registration = new McpRegistration($artisanPath);
    $written = $registration->register($projectDir);

    expect($written)->toContain($projectDir . '/.vscode/mcp.json');
    expect($written)->toContain($homeDir . '/.config/Code/User/mcp.json');

    foreach ($written as $path) {
        expect(is_file($path))->toBeTrue();
        removePath($path);
    }

    removePath($projectDir);
    removePath($homeDir . '/.config/Code/User');
    removePath($homeDir . '/.config/Code');
    removePath($homeDir . '/.config/Claude');
    removePath($homeDir . '/.config/Codex');
    removePath($homeDir . '/.config');
    removePath($homeDir . '/.claude/mcp');
    removePath($homeDir . '/.claude');
    removePath($homeDir);

    putenv('HOME');
    unset($_SERVER['HOME']);
    if (is_file($artisanPath)) {
        unlink($artisanPath);
    }
});
