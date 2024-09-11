<?php

namespace Tests\Logging;

use PHPUnit\Framework\TestCase;
use HybridRAG\Logging\Logger;

class LoggerTest extends TestCase
{
    private $logPath;
    private $logger;

    protected function setUp(): void
    {
        $this->logPath = sys_get_temp_dir() . '/test_log.log';
        if (!file_exists($this->logPath)) {
            touch($this->logPath); // Ensure the log file is created
        }
        $this->logger = new Logger('test_logger', $this->logPath, 'DEBUG', false); // Ensure debug mode is off for file logging
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    public function testDebug()
    {
        $this->logger->debug('Debug message');
        $this->assertLogContains('DEBUG: Debug message');
    }

    public function testInfo()
    {
        $this->logger->info('Info message');
        $this->assertLogContains('INFO: Info message');
    }

    public function testWarning()
    {
        $this->logger->warning('Warning message');
        $this->assertLogContains('WARNING: Warning message');
    }

    public function testError()
    {
        $this->logger->error('Error message');
        $this->assertLogContains('ERROR: Error message');
    }

    public function testCritical()
    {
        $this->logger->critical('Critical message');
        $this->assertLogContains('CRITICAL: Critical message');
    }

    public function testLogWithContext()
    {
        $this->logger->info('Message with context', ['key' => 'value']);
        $this->assertLogContains('INFO: Message with context {"key":"value"}');
    }

    private function assertLogContains(string $expected)
    {
        $logContent = file_get_contents($this->logPath);
        $this->assertStringContainsString($expected, $logContent);
    }
}
