<?php

namespace App\Utils;

/**
 * Logger Factory singleton
 * 
 * Provides global access to the logger instance
 */
class LoggerFactory
{
    /**
     * @var Logger The logger instance
     */
    private static $logger = null;
    
    /**
     * Get the logger instance
     * 
     * @return Logger
     */
    public static function getLogger(): Logger
    {
        if (self::$logger === null) {
            self::$logger = new Logger();
        }
        
        return self::$logger;
    }
    
    /**
     * Set a custom logger instance
     * 
     * @param Logger $logger The logger instance
     * @return void
     */
    public static function setLogger(Logger $logger): void
    {
        self::$logger = $logger;
    }
}