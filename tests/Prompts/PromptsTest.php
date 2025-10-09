<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Prompts\BreakingChangePatternPrompt;
use GoldenPathDigital\LaravelAscend\Prompts\PackageUpgradePrompt;
use GoldenPathDigital\LaravelAscend\Prompts\UpgradeFoundationPrompt;

test('upgrade foundation prompt has correct name', function () {
    $prompt = new UpgradeFoundationPrompt();

    expect($prompt->name())->toBe('upgrade-foundation');
});

test('upgrade foundation prompt returns array structure', function () {
    $prompt = new UpgradeFoundationPrompt();
    $array = $prompt->toArray();

    expect($array)->toBeArray()
        ->toHaveKey('name')
        ->toHaveKey('description')
        ->toHaveKey('arguments');

    expect($array['name'])->toBe('upgrade-foundation');
    expect($array['description'])->toBeString();
    expect($array['arguments'])->toBeArray();
});

test('upgrade foundation prompt has content', function () {
    $prompt = new UpgradeFoundationPrompt();
    $content = $prompt->getContent();

    expect($content)->toBeString()
        ->toContain('Laravel Upgrade Guidelines')
        ->toContain('Foundational Principles')
        ->toContain('Upgrade Process');
});

test('package upgrade prompt has correct name', function () {
    $prompt = new PackageUpgradePrompt();

    expect($prompt->name())->toBe('package-upgrade-guide');
});

test('package upgrade prompt returns array structure', function () {
    $prompt = new PackageUpgradePrompt();
    $array = $prompt->toArray();

    expect($array)->toBeArray()
        ->toHaveKey('name')
        ->toHaveKey('description')
        ->toHaveKey('arguments');

    expect($array['name'])->toBe('package-upgrade-guide');
});

test('package upgrade prompt has comprehensive content', function () {
    $prompt = new PackageUpgradePrompt();
    $content = $prompt->getContent();

    expect($content)->toBeString()
        ->toContain('Package Upgrade Guidelines')
        ->toContain('Package Compatibility Strategy')
        ->toContain('laravel/sanctum')
        ->toContain('spatie');
});

test('breaking change pattern prompt has correct name', function () {
    $prompt = new BreakingChangePatternPrompt();

    expect($prompt->name())->toBe('breaking-change-patterns');
});

test('breaking change pattern prompt returns array structure', function () {
    $prompt = new BreakingChangePatternPrompt();
    $array = $prompt->toArray();

    expect($array)->toBeArray()
        ->toHaveKey('name')
        ->toHaveKey('description')
        ->toHaveKey('arguments');
});

test('breaking change pattern prompt covers common patterns', function () {
    $prompt = new BreakingChangePatternPrompt();
    $content = $prompt->getContent();

    expect($content)->toBeString()
        ->toContain('Method Signature Changes')
        ->toContain('Namespace Changes')
        ->toContain('Facade Changes')
        ->toContain('Blade Directive Changes');
});

test('all prompts implement PromptInterface', function () {
    $prompts = [
        new UpgradeFoundationPrompt(),
        new PackageUpgradePrompt(),
        new BreakingChangePatternPrompt(),
    ];

    foreach ($prompts as $prompt) {
        expect($prompt)->toBeInstanceOf(\GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\PromptInterface::class);
    }
});
