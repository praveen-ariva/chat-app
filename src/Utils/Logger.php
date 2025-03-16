<?php

namespace App\Utils;

/**
 * Simple Logger class for the application
 */
class Logger
{
    /**
     * @var string Log file path
     */
    private $logFile;
    
    /**
     * @var string Current environment
     */
    private $environment;
    
    /**
     * Constructor
     * 
     * @param string|null $logFile Path to log file
     * @param string|null $environment Current environment
     */
    public function __construct(?string $logFile = null, ?string $environment = null)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/app.log';
        $this->environment = $environment ?? ($_ENV['APP_ENV'] ?? 'development');
        
        // Ensure logs directory exists
        $logsDir = dirname($this->logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
    }
    
    /**
     * Log an info message
     * 
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }
    
    /**
     * Log an error message
     * 
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }
    
    /**
     * Log a debug message (only in development environment)
     * 
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        if ($this->environment === 'development') {
            $this->log('DEBUG', $message, $context);
        }
    }
    
    /**
     * Log a message
     * 
     * @param string $level Log level
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextString = empty($context) ? '' : ' - ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        $logLine = "[$timestamp] [$level] $message$contextString" . PHP_EOL;
        
        file_put_contents($this->logFile, $logLine, FILE_APPEND);
        
        // Also use PHP's native error_log in development environment
        if ($this->environment === 'development') {
            error_log($logLine);
        }
    }
}