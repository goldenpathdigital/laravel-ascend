<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Tools\Analysis\AnalyzeCurrentVersionTool;
use GoldenPathDigital\LaravelAscend\Tools\Analysis\GetUpgradePathTool;
use GoldenPathDigital\LaravelAscend\Tools\Analysis\CheckPhpCompatibilityTool;

beforeEach(function () {
    $this->knowledgeBase = KnowledgeBaseService::createDefault();
});

test('analyze current version tool detects Laravel version from composer', function () {
    $tool = new AnalyzeCurrentVersionTool($this->knowledgeBase);
    
    $result = $tool->execute([
        'project_root' => __DIR__ . '/../fixtures/project-basic',
    ]);
    
    expect($result)->toHaveKey('ok');
    expect($result['ok'])->toBeTrue();
    expect($result)->toHaveKey('data');
    expect($result['data'])->toHaveKey('current_version');
});

test('analyze current version tool provides framework info', function () {
    $tool = new AnalyzeCurrentVersionTool($this->knowledgeBase);
    
    $result = $tool->execute([
        'project_root' => __DIR__ . '/../fixtures/project-basic',
    ]);
    
    expect($result['data'])->toHaveKey('framework_info');
    expect($result['data']['framework_info'])->toHaveKey('laravel');
});

test('get upgrade path tool finds valid path', function () {
    $tool = new GetUpgradePathTool($this->knowledgeBase);
    
    $result = $tool->execute([
        'from_version' => '10',
        'to_version' => '11',
    ]);
    
    expect($result['ok'])->toBeTrue();
    expect($result['data'])->toHaveKey('upgrade_path');
    expect($result['data']['upgrade_path'])->toHaveKey('identifier');
});

test('get upgrade path tool handles invalid versions', function () {
    $tool = new GetUpgradePathTool($this->knowledgeBase);
    
    $result = $tool->execute([
        'from_version' => '999',
        'to_version' => '1000',
    ]);
    
    expect($result['ok'])->toBeFalse();
    expect($result)->toHaveKey('error');
});

test('check php compatibility tool validates requirements', function () {
    $tool = new CheckPhpCompatibilityTool($this->knowledgeBase);
    
    $result = $tool->execute([
        'php_version' => '8.2',
        'target_laravel_version' => '11',
    ]);
    
    expect($result['ok'])->toBeTrue();
    expect($result['data'])->toHaveKey('is_compatible');
});

test('check php compatibility detects incompatibility', function () {
    $tool = new CheckPhpCompatibilityTool($this->knowledgeBase);
    
    $result = $tool->execute([
        'php_version' => '7.4',
        'target_laravel_version' => '11',
    ]);
    
    expect($result['data']['is_compatible'])->toBeFalse();
});

test('tools return timing information', function () {
    $tool = new AnalyzeCurrentVersionTool($this->knowledgeBase);
    
    $result = $tool->execute([
        'project_root' => __DIR__ . '/../fixtures/project-basic',
    ]);
    
    expect($result)->toHaveKey('timings');
    expect($result['timings'])->toHaveKey('ms');
    expect($result['timings']['ms'])->toBeFloat();
});

test('tools include schema version', function () {
    $tool = new GetUpgradePathTool($this->knowledgeBase);
    
    $result = $tool->execute([
        'from_version' => '10',
        'to_version' => '11',
    ]);
    
    expect($result)->toHaveKey('schema_version');
    expect($result['schema_version'])->toBe('1.0.0');
});
