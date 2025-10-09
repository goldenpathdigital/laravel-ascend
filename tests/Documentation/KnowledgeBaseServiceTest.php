<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;

beforeEach(function (): void {
    $basePath = dirname(__DIR__, 2) . '/resources/knowledge-base';
    $this->service = KnowledgeBaseService::createDefault($basePath);
});

it('provides summary details for the knowledge base', function (): void {
    $summary = $this->service->getSummary();

    expect($summary)
        ->toHaveKey('knowledge_base_version')
        ->toHaveKey('last_updated')
        ->toHaveKey('laravel_versions_covered')
        ->toHaveKey('pattern_count')
        ->toHaveKey('breaking_change_document_count')
        ->toHaveKey('search_entry_count');
});

it('fetches individual breaking change entries', function (): void {
    $entry = $this->service->getBreakingChangeEntry('laravel-7', 'symfony-5-method-signatures');

    expect($entry)
        ->toHaveKey('id', 'symfony-5-method-signatures')
        ->toHaveKey('slug', 'laravel-7');
});

it('fetches upgrade path data by identifier', function (): void {
    $path = $this->service->getUpgradePath('6-to-7');

    expect($path)
        ->toHaveKey('from', '6.x')
        ->toHaveKey('to', '7.0');
});

it('resolves upgrade path identifiers using versions', function (): void {
    $identifier = $this->service->resolveUpgradePathIdentifier('6.x', '7.x');

    expect($identifier)->toBe('6-to-7');

    $path = $this->service->getUpgradePathByVersions('6.x', '7.x');

    expect($path)->toHaveKey('from', '6.x');
});

it('resolves breaking change slug from version input', function (): void {
    $slug = $this->service->resolveBreakingChangeSlug('7.x');

    expect($slug)->toBe('laravel-7');
});
