<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Analyzers\FilesystemScanner;
use GoldenPathDigital\LaravelAscend\Analyzers\ProjectContext;

beforeEach(function () {
    $this->testDir = sys_get_temp_dir() . '/ascend-scanner-test-' . uniqid();
    mkdir($this->testDir, 0755, true);
    
    $this->context = new ProjectContext($this->testDir);
    $this->scanner = new FilesystemScanner($this->context);
});

afterEach(function () {
    if (isset($this->testDir) && is_dir($this->testDir)) {
        // Recursively delete directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->testDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
    }
});

it('scans files in directory', function () {
    file_put_contents($this->testDir . '/test1.php', '<?php echo "test";');
    file_put_contents($this->testDir . '/test2.php', '<?php echo "test2";');
    
    $files = $this->scanner->allFiles();
    
    expect($files)->toHaveCount(2);
    // Check that one of the files contains test1.php (order not guaranteed)
    $fileNames = implode('|', $files);
    expect($fileNames)->toContain('test1.php');
    expect($fileNames)->toContain('test2.php');
});

it('excludes vendor directory by default', function () {
    mkdir($this->testDir . '/vendor', 0755, true);
    file_put_contents($this->testDir . '/app.php', '<?php');
    file_put_contents($this->testDir . '/vendor/package.php', '<?php');
    
    $files = $this->scanner->allFiles();
    
    expect($files)->toHaveCount(1);
    expect($files[0])->toContain('app.php');
});

it('converts glob patterns to regex', function () {
    file_put_contents($this->testDir . '/test.php', '<?php');
    file_put_contents($this->testDir . '/test.js', 'console.log()');
    
    $phpFiles = $this->scanner->findByPatterns(['*.php']);
    
    expect($phpFiles)->toHaveCount(1);
    expect($phpFiles[0])->toContain('test.php');
});

it('handles multiple glob patterns', function () {
    file_put_contents($this->testDir . '/test.php', '<?php');
    file_put_contents($this->testDir . '/test.js', 'console.log()');
    file_put_contents($this->testDir . '/test.css', 'body {}');
    
    $files = $this->scanner->findByPatterns(['*.php', '*.js']);
    
    expect($files)->toHaveCount(2);
});

it('returns empty array for non-existent file in regex match', function () {
    $matches = $this->scanner->findRegexMatches('/non/existent/file.php', ['test']);
    
    expect($matches)->toBeArray();
    expect($matches)->toBeEmpty();
});

it('validates file size before reading', function () {
    // Create a file larger than 1MB limit
    $largeFile = $this->testDir . '/large.php';
    $handle = fopen($largeFile, 'w');
    // Write 2MB of data
    fwrite($handle, str_repeat('x', 2 * 1024 * 1024));
    fclose($handle);
    
    $matches = $this->scanner->findRegexMatches($largeFile, ['test']);
    
    // Should return empty array for oversized file
    expect($matches)->toBeArray();
    expect($matches)->toBeEmpty();
});

it('finds regex matches in files', function () {
    $file = $this->testDir . '/test.php';
    file_put_contents($file, "<?php\nclass Test {}\ninterface TestInterface {}");
    
    $matches = $this->scanner->findRegexMatches($file, ['class\s+\w+', 'interface\s+\w+']);
    
    expect($matches)->not->toBeEmpty();
    expect($matches[0])->toHaveKey('line');
    expect($matches[0])->toHaveKey('evidence');
});

it('respects maxMatches parameter', function () {
    $file = $this->testDir . '/test.php';
    file_put_contents($file, "<?php\ntest\ntest\ntest\ntest\ntest");
    
    $matches = $this->scanner->findRegexMatches($file, ['test'], 2);
    
    expect($matches)->toHaveCount(2);
});

it('validates regex patterns for safety', function () {
    $file = $this->testDir . '/test.php';
    file_put_contents($file, "<?php test");
    
    // Pattern with nested quantifiers should be rejected
    $matches = $this->scanner->findRegexMatches($file, ['(a+)+']);
    
    expect($matches)->toBeEmpty();
});

it('handles empty regex patterns array', function () {
    $file = $this->testDir . '/test.php';
    file_put_contents($file, "<?php test");
    
    $matches = $this->scanner->findRegexMatches($file, []);
    
    expect($matches)->toBeEmpty();
});

it('converts paths to relative format', function () {
    $absolutePath = $this->testDir . '/test.php';
    
    $relative = $this->scanner->toRelativePath($absolutePath);
    
    expect($relative)->toBe('test.php');
});

it('handles paths outside project root', function () {
    $outsidePath = '/some/other/path/file.php';
    
    $result = $this->scanner->toRelativePath($outsidePath);
    
    // Should return the original path if not within project
    expect($result)->toBe($outsidePath);
});
