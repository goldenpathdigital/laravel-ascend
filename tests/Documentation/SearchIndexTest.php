<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Documentation\DocumentationLoader;
use GoldenPathDigital\LaravelAscend\Documentation\DocumentationParser;
use GoldenPathDigital\LaravelAscend\Documentation\SearchIndex;

beforeEach(function (): void {
    $basePath = dirname(__DIR__, 2) . '/resources/knowledge-base';
    $loader = new DocumentationLoader($basePath, new DocumentationParser());
    $this->searchIndex = new SearchIndex($loader);
});

it('returns relevant results for breaking change search terms', function (): void {
    $results = $this->searchIndex->search('Exception Handler signatures');

    expect($results)->toBeArray()->not->toBeEmpty();
    expect($results[0])
        ->toHaveKey('type', 'breaking_change')
        ->toHaveKey('title')
        ->toHaveKey('score')
        ->and($results[0]['score'])->toBeGreaterThan(0);
});

it('returns pattern matches with metadata for SwiftMailer query', function (): void {
    $results = $this->searchIndex->search('SwiftMailer');

    $match = null;

    foreach ($results as $result) {
        if ($result['id'] === 'swiftmailer-to-symfony-mailer') {
            $match = $result;
            break;
        }
    }

    expect($match)->not->toBeNull();
    expect($match['metadata'])->toBeArray()->toHaveKey('category');
});

it('respects the result limit', function (): void {
    $results = $this->searchIndex->search('Laravel upgrade', 3);

    expect(count($results))->toBeLessThanOrEqual(3);
});

it('returns an empty array for an empty query', function (): void {
    expect($this->searchIndex->search(''))->toBe([]);
});
