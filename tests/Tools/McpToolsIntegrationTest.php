<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Server\AscendServer;

beforeEach(function (): void {
    $this->projectRoot = __DIR__ . '/../fixtures/project-basic';
    $this->server = AscendServer::createDefault();
});

it('analyzes the current project version', function (): void {
    $response = $this->server->callTool('analyze_current_version', [
        'project_root' => $this->projectRoot,
    ]);

    expect($response)
        ->toHaveKey('ok', true)
        ->and($response['data'])
        ->toHaveKey('laravel_constraint', '^7.0');
});

it('scans for breaking changes between versions', function (): void {
    $response = $this->server->callTool('scan_breaking_changes', [
        'project_root' => $this->projectRoot,
        'from' => '6.x',
        'to' => '7.x',
    ]);

    expect($response['ok'])->toBeTrue();
    expect($response['data']['matches'])
        ->not->toBeEmpty();
});

it('finds usage of knowledge base patterns', function (): void {
    $response = $this->server->callTool('find_usage_patterns', [
        'project_root' => $this->projectRoot,
        'pattern' => 'swiftmailer-to-symfony-mailer',
    ]);

    expect($response['ok'])->toBeTrue();
    expect($response['data']['results'][0]['matches'])
        ->not->toBeEmpty();
});

it('generates an upgrade checklist with sequence steps', function (): void {
    $response = $this->server->callTool('generate_upgrade_checklist', [
        'project_root' => $this->projectRoot,
        'from' => '6.x',
        'to' => '7.x',
    ]);

    expect($response['ok'])->toBeTrue();
    expect($response['data']['checklist'])
        ->toBeArray();
});

it('generates an upgrade checklist with default version range', function (): void {
    $response = $this->server->callTool('generate_upgrade_checklist', [
        'project_root' => $this->projectRoot,
    ]);

    expect($response['ok'])->toBeTrue();
    expect($response['data']['from'])->toBe('7.x');
    expect($response['data']['to'])->toBe('8.0');
});

it('validates upgrade step completion for a breaking change', function (): void {
    $response = $this->server->callTool('validate_upgrade_step', [
        'project_root' => $this->projectRoot,
        'from' => '6.x',
        'to' => '7.x',
        'change' => 'symfony-5-method-signatures',
    ]);

    expect($response['ok'])->toBeTrue();
    expect($response['data']['validated'])->toBeFalse();
});

it('validates upgrade step using default version range', function (): void {
    $response = $this->server->callTool('validate_upgrade_step', [
        'project_root' => $this->projectRoot,
        'change' => 'php-version-requirement',
    ]);

    expect($response['ok'])->toBeTrue();
    expect($response['data']['from'])->toBe('7.x');
    expect($response['data']['to'])->toBe('8.x');
});

it('scans for breaking changes using default version range', function (): void {
    $response = $this->server->callTool('scan_breaking_changes', [
        'project_root' => $this->projectRoot,
    ]);

    expect($response['ok'])->toBeTrue();
    expect($response['data']['from'])->toBe('7.x');
    expect($response['data']['to'])->toBe('8.x');
});

it('checks package compatibility against a target', function (): void {
    $response = $this->server->callTool('check_package_compatibility', [
        'project_root' => $this->projectRoot,
        'package' => 'laravel/framework',
        'target' => '10.x',
    ]);

    expect($response['ok'])->toBeTrue();
    expect($response['data']['compatible'])->toBeFalse();
});

it('suggests package updates for future upgrades', function (): void {
    $response = $this->server->callTool('suggest_package_updates', [
        'project_root' => $this->projectRoot,
        'target' => '10.x',
    ]);

    expect($response['ok'])->toBeTrue();
    expect($response['data']['suggestions'])
        ->not->toBeEmpty();
});
