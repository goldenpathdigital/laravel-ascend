<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Server\Mcp\McpRequestHandler;
use GoldenPathDigital\LaravelAscend\Server\AscendServer;

beforeEach(function () {
    $this->server = AscendServer::createDefault();
    $this->handler = new McpRequestHandler($this->server);
});

test('it reads resources via MCP protocol', function () {
    // First initialize
    $initRequest = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'test-client',
                'version' => '1.0.0',
            ],
        ],
    ];

    $initResponse = $this->handler->handleRequest($initRequest);
    expect($initResponse)->toBeArray()
        ->and($initResponse['result'])->toHaveKey('protocolVersion');

    // List resources
    $listRequest = [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'resources/list',
        'params' => [],
    ];

    $listResponse = $this->handler->handleRequest($listRequest);
    expect($listResponse)->toBeArray()
        ->and($listResponse['result'])->toHaveKey('resources')
        ->and($listResponse['result']['resources'])->toBeArray()
        ->and(count($listResponse['result']['resources']))->toBeGreaterThan(0);

    // Get the first resource URI
    $firstResource = $listResponse['result']['resources'][0];
    expect($firstResource)->toHaveKey('uri');
    $resourceUri = $firstResource['uri'];

    // Read the resource
    $readRequest = [
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'resources/read',
        'params' => [
            'uri' => $resourceUri,
        ],
    ];

    $readResponse = $this->handler->handleRequest($readRequest);
    expect($readResponse)->toBeArray()
        ->and($readResponse)->toHaveKey('result')
        ->and($readResponse['result'])->toHaveKey('contents')
        ->and($readResponse['result']['contents'])->toBeArray()
        ->and(count($readResponse['result']['contents']))->toBeGreaterThan(0);

    $content = $readResponse['result']['contents'][0];
    expect($content)->toHaveKey('uri')
        ->and($content)->toHaveKey('mimeType')
        ->and($content)->toHaveKey('text')
        ->and($content['uri'])->toBe($resourceUri);
});

test('it returns error for non-existent resource', function () {
    // First initialize
    $initRequest = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'test-client',
                'version' => '1.0.0',
            ],
        ],
    ];

    $this->handler->handleRequest($initRequest);

    // Try to read a non-existent resource
    $readRequest = [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'resources/read',
        'params' => [
            'uri' => 'ascend://non-existent-resource',
        ],
    ];

    $readResponse = $this->handler->handleRequest($readRequest);
    expect($readResponse)->toBeArray()
        ->and($readResponse)->toHaveKey('error')
        ->and($readResponse['error'])->toHaveKey('code')
        ->and($readResponse['error'])->toHaveKey('message')
        ->and($readResponse['error']['code'])->toBe(-32603)
        ->and($readResponse['error']['message'])->toContain('not found');
});
