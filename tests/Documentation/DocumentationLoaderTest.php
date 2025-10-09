<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Documentation\DocumentationLoader;
use GoldenPathDigital\LaravelAscend\Documentation\DocumentationParser;

beforeEach(function (): void {
    $basePath = dirname(__DIR__, 2) . '/resources/knowledge-base';
    $this->loader = new DocumentationLoader($basePath, new DocumentationParser());
});

it('loads knowledge base metadata from the index', function (): void {
    $index = $this->loader->loadIndex();

    expect($index)
        ->toBeArray()
        ->toHaveKey('knowledge_base_version')
        ->toHaveKey('laravel_versions_covered');

    expect($this->loader->getKnowledgeBaseVersion())->toBeString();
    expect($this->loader->getLaravelVersionsCovered())->toBeArray()->not->toBeEmpty();
});

it('loads a breaking change document by slug', function (): void {
    $document = $this->loader->loadBreakingChangeDocument('laravel-7');

    expect($document)
        ->toBeArray()
        ->toHaveKey('version', '7.0')
        ->toHaveKey('breaking_changes');

    expect($document['breaking_changes'])->toBeArray()->not->toBeEmpty();
});

it('aggregates individual breaking change entries', function (): void {
    $entries = $this->loader->loadBreakingChangeEntries();

    expect($entries)
        ->toBeArray()
        ->toHaveKey('laravel-7::symfony-5-method-signatures');

    $entry = $entries['laravel-7::symfony-5-method-signatures'];

    expect($entry)
        ->toHaveKey('id', 'symfony-5-method-signatures')
        ->toHaveKey('title')
        ->toHaveKey('version', '7.0');
});

it('loads pattern documents and individual patterns', function (): void {
    $patterns = $this->loader->loadPatternDocuments();

    expect($patterns)
        ->toBeArray()
        ->toHaveKey('accessor-mutator-attribute-syntax');

    $pattern = $this->loader->loadPatternDocument('accessor-mutator-attribute-syntax');

    expect($pattern)
        ->toHaveKey('name')
        ->toHaveKey('description');
});

it('loads upgrade path data with sequencing', function (): void {
    $upgradePaths = $this->loader->loadUpgradePaths();

    expect($upgradePaths)
        ->toBeArray()
        ->toHaveKey('upgrade_paths')
        ->and($upgradePaths['upgrade_paths'])->toHaveKey('6-to-7');

    $path = $upgradePaths['upgrade_paths']['6-to-7'];

    expect($path)
        ->toHaveKey('sequence')
        ->and($path['sequence'])->toBeArray()->not->toBeEmpty();
});

