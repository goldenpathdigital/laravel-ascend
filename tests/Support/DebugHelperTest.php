r<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Support\DebugHelper;

beforeEach(function () {
    DebugHelper::disable(); // Reset to production mode before each test
});

afterEach(function () {
    DebugHelper::disable(); // Clean up after tests
});

it('starts disabled by default', function () {
    expect(DebugHelper::isEnabled())->toBeFalse();
});

it('can be enabled', function () {
    DebugHelper::enable();
    expect(DebugHelper::isEnabled())->toBeTrue();
});

it('can be disabled', function () {
    DebugHelper::enable();
    DebugHelper::disable();
    expect(DebugHelper::isEnabled())->toBeFalse();
});

it('formats error without debug details in production mode', function () {
    $message = DebugHelper::formatError('Error occurred', ['detail' => 'value']);
    expect($message)->toBe('Error occurred');
});

it('formats error with debug details in debug mode', function () {
    DebugHelper::enable();
    $message = DebugHelper::formatError('Error occurred', ['detail' => 'value']);
    expect($message)->toContain('Error occurred');
    expect($message)->toContain('[Debug:');
    expect($message)->toContain('detail: value');
});

it('handles array values in debug details', function () {
    DebugHelper::enable();
    $message = DebugHelper::formatError('Error', ['data' => ['key' => 'val']]);
    expect($message)->toContain('data: {');
    expect($message)->toContain('key');
});

it('handles boolean values in debug details', function () {
    DebugHelper::enable();
    $message = DebugHelper::formatError('Error', ['flag' => true, 'other' => false]);
    expect($message)->toContain('flag: true');
    expect($message)->toContain('other: false');
});

it('handles null values in debug details', function () {
    DebugHelper::enable();
    $message = DebugHelper::formatError('Error', ['value' => null]);
    expect($message)->toContain('value: null');
});

it('sanitizes path to basename in production mode', function () {
    $path = '/full/path/to/file.php';
    $sanitized = DebugHelper::sanitizePath($path);
    expect($sanitized)->toBe('file.php');
});

it('returns full path in debug mode', function () {
    DebugHelper::enable();
    $path = '/full/path/to/file.php';
    $sanitized = DebugHelper::sanitizePath($path);
    expect($sanitized)->toBe($path);
});

it('makes path relative to base path in production mode', function () {
    $path = '/var/www/project/src/file.php';
    $basePath = '/var/www/project';
    $sanitized = DebugHelper::sanitizePath($path, $basePath);
    expect($sanitized)->toBe('.../src/file.php');
});

it('returns empty debug message for empty details array', function () {
    DebugHelper::enable();
    $message = DebugHelper::formatError('Error', []);
    expect($message)->toBe('Error');
});
