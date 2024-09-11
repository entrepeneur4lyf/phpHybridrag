<?php

declare(strict_types=1);

namespace HybridRAG\Logging;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Class Logger
 *
 * This class provides a wrapper around Monolog for logging functionality.
 */
/**
 * Class Logger
 *
 * This class provides a wrapper around Monolog for logging functionality.
 */
class Logger
{
    private MonologLogger $logger;

    /**
     * Logger constructor.
     *
     * @param string $name The name of the logger
     * @param string $logPath The path to the log file
     * @param string $logLevel The log level (default: 'info')
     * @param bool $debugMode Whether to enable debug mode (default: false)
     */
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

    /**
     * Log a debug message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Log a critical message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }
}
