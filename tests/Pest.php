<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

// Close Mockery after each test in Mcp directory
afterEach(function () {
    if (class_exists('Mockery')) {
        Mockery::close();
    }
})->in('Mcp');

// Define env() helper for tests if not already defined
if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        return $value;
    }
}
