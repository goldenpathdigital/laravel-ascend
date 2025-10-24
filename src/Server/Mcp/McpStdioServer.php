<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Server\Mcp;

final class McpStdioServer
{
    /** @var McpRequestHandler */
    private $handler;

    /** @var int */
    private $heartbeatInterval;

    public function __construct(
        McpRequestHandler $handler,
        int $heartbeatInterval = 30
    ) {
        $this->handler = $handler;
        $this->heartbeatInterval = max(10, $heartbeatInterval); // Minimum 10 seconds
    }

    public function run(): void
    {
        $input = fopen('php://stdin', 'r');
        $output = fopen('php://stdout', 'w');

        if ($input === false || $output === false) {
            throw new \RuntimeException('Unable to open STDIN/STDOUT streams.');
        }

        // Set non-blocking mode on input to allow periodic processing
        stream_set_blocking($input, false);

        try {
            $lastActivity = time();
            $heartbeatInterval = $this->heartbeatInterval;

            while (!feof($input)) {
                $line = fgets($input);

                if ($line === false) {
                    // No data available - check if we need to send heartbeat
                    $now = time();
                    if (($now - $lastActivity) >= $heartbeatInterval) {
                        // Send a ping notification to keep connection alive
                        // Using notification (no id) so client doesn't need to respond
                        $ping = json_encode([
                            'jsonrpc' => '2.0',
                            'method' => 'notifications/heartbeat',
                            'params' => [
                                'timestamp' => $now,
                            ],
                        ]);
                        fwrite($output, $ping . PHP_EOL);
                        fflush($output);
                        $lastActivity = $now;
                    }

                    // Sleep briefly to avoid busy-waiting
                    usleep(100000); // 100ms
                    continue;
                }

                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $lastActivity = time();
                $response = $this->handler->handleRaw($line);

                if ($response === '') {
                    continue;
                }

                fwrite($output, $response . PHP_EOL);
                fflush($output);
            }
        } finally {
            fclose($input);
            fclose($output);
        }
    }
}
