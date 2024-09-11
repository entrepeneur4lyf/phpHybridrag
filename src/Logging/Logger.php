<?php

declare(strict_types=1);

namespace HybridRAG\Logging;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class Logger
{
    private MonologLogger $logger;

    public function __construct(string $name, string $logPath, string $logLevel = 'info', bool $debugMode = false)
    {
        $this->logger = new MonologLogger($name);

        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s.u",
            true,
            true
        );

        $streamHandler = new StreamHandler('php://stderr', $debugMode ? MonologLogger::DEBUG : MonologLogger::INFO);
        $streamHandler->setFormatter($formatter);
        $this->logger->pushHandler($streamHandler);

        $fileHandler = new RotatingFileHandler($logPath, 0, constant(MonologLogger::class . '::' . strtoupper($logLevel)));
        $fileHandler->setFormatter($formatter);
        $this->logger->pushHandler($fileHandler);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }
}