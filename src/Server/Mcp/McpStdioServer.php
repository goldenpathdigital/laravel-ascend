<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Server\Mcp;

final class McpStdioServer
{
    /** @var McpRequestHandler */
    private $handler;

    public function __construct(
        McpRequestHandler $handler,
    ) {
        $this->handler = $handler;
    }

    public function run(): void
    {
        $input = fopen('php://stdin', 'r');
        $output = fopen('php://stdout', 'w');

        if ($input === false || $output === false) {
            throw new \RuntimeException('Unable to open STDIN/STDOUT streams.');
        }

        try {
            while (!feof($input)) {
                $line = fgets($input);

                if ($line === false) {
                    break;
                }

                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $response = $this->handler->handleRaw($line);

                if ($response === '') {
                    continue;
                }

                fwrite($output, $response . PHP_EOL);
            }
        } finally {
            fclose($input);
            fclose($output);
        }
    }
}
