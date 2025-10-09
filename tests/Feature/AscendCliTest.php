<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Server\AscendServer;

it('exposes knowledge base information via the MCP server', function (): void {
    $server = AscendServer::createDefault();
    $info = $server->getKnowledgeBaseInfo();

    expect($info)
        ->toHaveKey('knowledge_base_version')
        ->and($info)
        ->toHaveKey('laravel_versions_covered');
});

it('searches the knowledge base via the MCP server', function (): void {
    $server = AscendServer::createDefault();
    $results = $server->searchKnowledgeBase('SwiftMailer', 2);

    expect($results)->not->toBeEmpty();
    expect($results[0])->toHaveKeys(['type', 'id', 'title']);
});
