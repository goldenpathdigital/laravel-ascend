<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Server\AscendServer;

beforeEach(function (): void {
    $knowledgeBasePath = dirname(__DIR__, 2) . '/resources/knowledge-base';
    $this->server = AscendServer::createDefault($knowledgeBasePath);
});

it('returns knowledge base info through the server facade', function (): void {
    $info = $this->server->getKnowledgeBaseInfo();

    expect($info)
        ->toHaveKey('knowledge_base_version')
        ->toHaveKey('pattern_count');
});

it('searches knowledge base content via server', function (): void {
    $results = $this->server->searchKnowledgeBase('SwiftMailer');

    expect($results)->toBeArray()->not->toBeEmpty();
    expect($results[0])->toHaveKey('type');
});

it('lists identifiers for future MCP tool integrations', function (): void {
    expect($this->server->listBreakingChangeSlugs())->toContain('laravel-7');
    expect($this->server->listPatternIdentifiers())->toContain('swiftmailer-to-symfony-mailer');
    expect($this->server->listUpgradePathIdentifiers())->toContain('6-to-7');
});

it('executes documentation tools via the registry', function (): void {
    $tools = $this->server->listToolNames();

    expect($tools)->toContain('search_upgrade_docs');
    expect($tools)->toContain('analyze_current_version');

    $response = $this->server->callTool('search_upgrade_docs', [
        'query' => 'SwiftMailer',
        'limit' => 2,
    ]);

    expect($response)
        ->toHaveKey('ok', true)
        ->toHaveKey('data')
        ->and($response['data']['results'])->not->toBeEmpty();

    expect($this->server->describeResources())->toBeArray();
    expect($this->server->describePrompts())->toBeArray();
});
