<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Server\AscendServer;
use GoldenPathDigital\LaravelAscend\Server\Mcp\McpRequestHandler;

beforeEach(function (): void {
    $this->handler = new McpRequestHandler(AscendServer::createDefault());
});

it('returns tool listings via JSON-RPC', function (): void {
    $init = $this->handler->handleRaw(json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [],
    ], JSON_THROW_ON_ERROR));

    expect(json_decode($init, true, 512, JSON_THROW_ON_ERROR)['result'])
        ->toHaveKey('serverInfo');

    $response = $this->handler->handleRaw(json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/list',
    ], JSON_THROW_ON_ERROR));

    $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

    expect($decoded)
        ->toHaveKey('result')
        ->and($decoded['result']['tools'])
        ->toBeArray()
        ->each(fn ($tool) => $tool->toHaveKey('name'));

    $toolNames = array_column($decoded['result']['tools'], 'name');
    expect($toolNames)->toContain('analyze_current_version');
});

it('invokes a tool through JSON-RPC interface', function (): void {
    $this->handler->handleRaw(json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [],
    ], JSON_THROW_ON_ERROR));

    $payload = json_encode([
        'jsonrpc' => '2.0',
        'id' => 99,
        'method' => 'tools/call',
        'params' => [
            'name' => 'search_upgrade_docs',
            'arguments' => ['query' => 'SwiftMailer'],
        ],
    ], JSON_THROW_ON_ERROR);

    $response = $this->handler->handleRaw($payload);
    $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

    expect($decoded)
        ->toHaveKey('result')
        ->and($decoded['result'])
        ->toHaveKey('content');
});
