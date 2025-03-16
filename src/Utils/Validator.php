<?php

namespace App\Utils;

/**
 * Validator class for input validation and sanitization
 */
class Validator
{
    /**
     * @var array Validation errors
     */
    private $errors = [];
    
    /**
     * Validate required fields
     * 
     * @param array $data The data to validate
     * @param array $fields The required fields
     * @return bool True if all required fields are present
     */
    public function required(array $data, array $fields): bool
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->errors[$field] = "$field is required";
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate string length
     * 
     * @param string $field The field name
     * @param string $value The value to validate
     * @param int $min The minimum length
     * @param int $max The maximum length
     * @return bool True if the string length is within the range
     */
    public function length(string $field, string $value, int $min = 1, ?int $max = null): bool
    {
        $length = strlen($value);
        
        if ($length < $min) {
            $this->errors[$field] = "$field must be at least $min characters";
            return false;
        }
        
        if ($max !== null && $length > $max) {
            $this->errors[$field] = "$field must be no more than $max characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate UUID format
     * 
     * @param string $field The field name
     * @param string $value The value to validate
     * @return bool True if the value is a valid UUID
     */
    public function uuid(string $field, string $value): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        
        if (!preg_match($pattern, $value)) {
            $this->errors[$field] = "$field must be a valid UUID";
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize a string
     * 
     * @param string $value The value to sanitize
     * @return string The sanitized value
     */
    public function sanitizeString(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize all string values in an array
     * 
     * @param array $data The data to sanitize
     * @return array The sanitized data
     */
    public function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Get validation errors
     * 
     * @return array The validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get first error message
     * 
     * @return string|null The first error message or null if no errors
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? array_values($this->errors)[0] : null;
    }
    
    /**
     * Check if validation has errors
     * 
     * @return bool True if validation has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * Reset validation errors
     * 
     * @return void
     */
    public function reset(): void
    {
        $this->errors = [];
    }
}