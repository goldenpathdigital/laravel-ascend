<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Tools\Documentation\GetBreakingChangeDetailsTool;
use GoldenPathDigital\LaravelAscend\Tools\Documentation\GetUpgradeGuideTool;
use GoldenPathDigital\LaravelAscend\Tools\Documentation\ListDeprecatedFeaturesTool;
use GoldenPathDigital\LaravelAscend\Tools\Documentation\SearchUpgradeDocsTool;

beforeEach(function (): void {
    $basePath = dirname(__DIR__, 2) . '/resources/knowledge-base';
    $this->knowledgeBase = KnowledgeBaseService::createDefault($basePath);
});

it('searches upgrade documents through the tool interface', function (): void {
    $tool = new SearchUpgradeDocsTool($this->knowledgeBase);
    $response = $tool->execute(['query' => 'SwiftMailer', 'limit' => 3]);

    expect($response)
        ->toHaveKey('ok', true)
        ->and($response['data']['results'])->not->toBeEmpty();
});

it('retrieves upgrade guide data with pattern details', function (): void {
    $tool = new GetUpgradeGuideTool($this->knowledgeBase);
    $response = $tool->execute(['from' => '6.x', 'to' => '7.x']);

    expect($response)->toHaveKey('ok', true);
    expect($response['data'])
        ->toHaveKey('identifier', '6-to-7')
        ->toHaveKey('sequence');
});

it('fetches breaking change details for a given version', function (): void {
    $tool = new GetBreakingChangeDetailsTool($this->knowledgeBase);
    $response = $tool->execute([
        'id' => 'symfony-5-method-signatures',
        'version' => '7.x',
    ]);

    expect($response)->toHaveKey('ok', true);
    expect($response['data']['change'])
        ->toHaveKey('id', 'symfony-5-method-signatures');
});

it('lists deprecated features using feature-removal categories', function (): void {
    $tool = new ListDeprecatedFeaturesTool($this->knowledgeBase);
    $response = $tool->execute(['version' => '10.x']);

    expect($response)->toHaveKey('ok', true);
    expect($response['data']['deprecated'])->toBeArray();
});

