<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class Handler
{
    public function report(Exception $exception)
    {
        // Legacy signature expecting update to Throwable
    }
}
