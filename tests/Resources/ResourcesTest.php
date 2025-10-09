<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Resources\BreakingChangesIndexResource;
use GoldenPathDigital\LaravelAscend\Resources\KnowledgeBaseSummaryResource;
use GoldenPathDigital\LaravelAscend\Resources\PatternsIndexResource;
use GoldenPathDigital\LaravelAscend\Resources\UpgradePathsResource;

beforeEach(function () {
    $this->knowledgeBase = KnowledgeBaseService::createDefault();
});

test('knowledge base summary resource has correct name', function () {
    $resource = new KnowledgeBaseSummaryResource($this->knowledgeBase);
    
    expect($resource->name())->toBe('ascend://knowledge-base/summary');
});

test('knowledge base summary resource returns array structure', function () {
    $resource = new KnowledgeBaseSummaryResource($this->knowledgeBase);
    $array = $resource->toArray();
    
    expect($array)->toBeArray()
        ->toHaveKey('uri')
        ->toHaveKey('name')
        ->toHaveKey('description')
        ->toHaveKey('mimeType')
        ->toHaveKey('content');
    
    expect($array['mimeType'])->toBe('application/json');
    expect($array['uri'])->toBe('ascend://knowledge-base/summary');
});

test('knowledge base summary resource has valid json content', function () {
    $resource = new KnowledgeBaseSummaryResource($this->knowledgeBase);
    $array = $resource->toArray();
    
    $decoded = json_decode($array['content'], true);
    
    expect($decoded)->toBeArray()
        ->and(json_last_error())->toBe(JSON_ERROR_NONE);
});

test('breaking changes index resource has correct name', function () {
    $resource = new BreakingChangesIndexResource($this->knowledgeBase);
    
    expect($resource->name())->toBe('ascend://knowledge-base/breaking-changes');
});

test('breaking changes index resource returns valid structure', function () {
    $resource = new BreakingChangesIndexResource($this->knowledgeBase);
    $array = $resource->toArray();
    
    expect($array)->toBeArray()
        ->toHaveKey('uri')
        ->toHaveKey('name')
        ->toHaveKey('description')
        ->toHaveKey('mimeType')
        ->toHaveKey('content');
    
    $decoded = json_decode($array['content'], true);
    
    expect($decoded)->toBeArray()
        ->toHaveKey('total')
        ->toHaveKey('versions');
});

test('breaking changes index contains version data', function () {
    $resource = new BreakingChangesIndexResource($this->knowledgeBase);
    $array = $resource->toArray();
    
    $decoded = json_decode($array['content'], true);
    
    expect($decoded['total'])->toBeGreaterThan(0)
        ->and($decoded['versions'])->toBeArray();
    
    if (!empty($decoded['versions'])) {
        $firstVersion = $decoded['versions'][0];
        expect($firstVersion)->toHaveKeys(['slug', 'version', 'title', 'change_count', 'uri']);
    }
});

test('patterns index resource has correct name', function () {
    $resource = new PatternsIndexResource($this->knowledgeBase);
    
    expect($resource->name())->toBe('ascend://knowledge-base/patterns');
});

test('patterns index resource returns valid structure', function () {
    $resource = new PatternsIndexResource($this->knowledgeBase);
    $array = $resource->toArray();
    
    expect($array)->toBeArray()
        ->toHaveKey('content');
    
    $decoded = json_decode($array['content'], true);
    
    expect($decoded)->toBeArray()
        ->toHaveKey('total')
        ->toHaveKey('patterns');
});

test('patterns index contains pattern data', function () {
    $resource = new PatternsIndexResource($this->knowledgeBase);
    $array = $resource->toArray();
    
    $decoded = json_decode($array['content'], true);
    
    expect($decoded['total'])->toBeGreaterThan(0)
        ->and($decoded['patterns'])->toBeArray();
    
    if (!empty($decoded['patterns'])) {
        $firstPattern = $decoded['patterns'][0];
        expect($firstPattern)->toHaveKeys(['id', 'name', 'description', 'versions_affected', 'uri']);
    }
});

test('upgrade paths resource has correct name', function () {
    $resource = new UpgradePathsResource($this->knowledgeBase);
    
    expect($resource->name())->toBe('ascend://knowledge-base/upgrade-paths');
});

test('upgrade paths resource returns valid structure', function () {
    $resource = new UpgradePathsResource($this->knowledgeBase);
    $array = $resource->toArray();
    
    expect($array)->toBeArray()
        ->toHaveKey('content');
    
    $decoded = json_decode($array['content'], true);
    
    expect($decoded)->toBeArray()
        ->toHaveKey('total')
        ->toHaveKey('paths');
});

test('all resources implement ResourceInterface', function () {
    $resources = [
        new KnowledgeBaseSummaryResource($this->knowledgeBase),
        new BreakingChangesIndexResource($this->knowledgeBase),
        new PatternsIndexResource($this->knowledgeBase),
        new UpgradePathsResource($this->knowledgeBase),
    ];
    
    foreach ($resources as $resource) {
        expect($resource)->toBeInstanceOf(\GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\ResourceInterface::class);
    }
});

test('all resources return valid json', function () {
    $resources = [
        new KnowledgeBaseSummaryResource($this->knowledgeBase),
        new BreakingChangesIndexResource($this->knowledgeBase),
        new PatternsIndexResource($this->knowledgeBase),
        new UpgradePathsResource($this->knowledgeBase),
    ];
    
    foreach ($resources as $resource) {
        $array = $resource->toArray();
        $decoded = json_decode($array['content'], true);
        
        expect(json_last_error())->toBe(JSON_ERROR_NONE, 
            'Resource ' . $resource->name() . ' should return valid JSON');
    }
});
