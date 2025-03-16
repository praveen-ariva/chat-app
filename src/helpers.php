<?php

use App\Utils\LoggerFactory;

/**
 * Log a debug message
 * 
 * This is a wrapper around the Logger::debug() method
 * 
 * @param string $message The message to log
 * @param mixed $data Additional data to log
 * @return void
 */
function app_debug(string $message, $data = null): void
{
    $logger = LoggerFactory::getLogger();
    $context = $data !== null ? ['data' => $data] : [];
    $logger->debug($message, $context);
}

/**
 * Log an info message
 * 
 * @param string $message The message to log
 * @param mixed $data Additional data to log
 * @return void
 */
function app_info(string $message, $data = null): void
{
    $logger = LoggerFactory::getLogger();
    $context = $data !== null ? ['data' => $data] : [];
    $logger->info($message, $context);
}

/**
 * Log an error message
 * 
 * @param string $message The message to log
 * @param mixed $data Additional data to log
 * @return void
 */
function app_error(string $message, $data = null): void
{
    $logger = LoggerFactory::getLogger();
    $context = $data !== null ? ['data' => $data] : [];
    $logger->error($message, $context);
}

/**
 * Sanitize input data
 * 
 * @param mixed $data The data to sanitize
 * @return mixed The sanitized data
 */
function sanitize_input($data)
{
    if (is_string($data)) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    } elseif (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_input($value);
        }
    }
    
    return $data;
}

/**
 * Generate a random UUID (v4)
 * 
 * @return string The generated UUID
 */
function generate_uuid(): string
{
    // Generate 16 random bytes
    $data = random_bytes(16);
    
    // Set version to 0100 (UUID v4)
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10 (variant 1)
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    // Format the UUID as a string
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}