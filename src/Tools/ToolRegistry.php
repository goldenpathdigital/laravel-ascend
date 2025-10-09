<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools;

use GoldenPathDigital\LaravelAscend\Exceptions\ToolException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ToolRegistry
{
    /**
     * @var array<string, ToolInterface>
     */
    private array $tools = [];

    private LoggerInterface $logger;
    private int $timeoutSeconds;

    public function __construct(?LoggerInterface $logger = null, int $timeoutSeconds = 60)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
        $this->logger->debug('Tool registered', ['tool' => $tool->getName()]);
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): ToolInterface
    {
        if (!$this->has($name)) {
            $this->logger->warning('Tool not found', ['tool' => $name]);
            throw ToolException::notRegistered($name);
        }

        return $this->tools[$name];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function invoke(string $name, array $payload = []): array
    {
        $tool = $this->get($name);
        $startTime = microtime(true);

        $this->logger->info('Tool execution started', [
            'tool' => $name,
            'payload_size' => count($payload),
        ]);

        try {
            // Execute with timeout using set_time_limit for long operations
            $originalLimit = ini_get('max_execution_time');
            if ($originalLimit !== false && $this->timeoutSeconds > 0) {
                set_time_limit($this->timeoutSeconds);
            }

            $result = $tool->execute($payload);

            // Restore original limit
            if ($originalLimit !== false) {
                set_time_limit((int) $originalLimit);
            }

            $duration = microtime(true) - $startTime;

            $this->logger->info('Tool execution completed', [
                'tool' => $name,
                'duration_ms' => round($duration * 1000, 2),
                'success' => $result['ok'] ?? false,
            ]);

            // Log warnings if present
            if (isset($result['warnings']) && is_array($result['warnings']) && count($result['warnings']) > 0) {
                $this->logger->warning('Tool execution warnings', [
                    'tool' => $name,
                    'warnings' => $result['warnings'],
                ]);
            }

            return $result;

        } catch (\Throwable $exception) {
            $duration = microtime(true) - $startTime;

            $this->logger->error('Tool execution failed', [
                'tool' => $name,
                'duration_ms' => round($duration * 1000, 2),
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            // Restore original limit in case of exception
            if (isset($originalLimit) && $originalLimit !== false) {
                set_time_limit((int) $originalLimit);
            }

            throw $exception;
        }
    }

    /**
     * @return array<int, string>
     */
    public function list(): array
    {
        return array_keys($this->tools);
    }

    /**
     * Set timeout for tool execution
     */
    public function setTimeout(int $seconds): void
    {
        $this->timeoutSeconds = $seconds;
        $this->logger->debug('Tool timeout updated', ['timeout' => $seconds]);
    }
}
