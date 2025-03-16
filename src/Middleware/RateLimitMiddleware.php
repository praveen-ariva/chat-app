<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Rate Limiting Middleware
 * 
 * Implements rate limiting to prevent API abuse
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * @var string Path to store rate limiting data
     */
    private $storagePath;
    
    /**
     * @var int Maximum number of requests allowed
     */
    private $maxRequests;
    
    /**
     * @var int Time window in minutes
     */
    private $perMinutes;
    
    /**
     * Constructor
     * 
     * @param string|null $storagePath Path to store rate limiting data
     * @param int|null $maxRequests Maximum number of requests allowed
     * @param int|null $perMinutes Time window in minutes
     */
    public function __construct(?string $storagePath = null, ?int $maxRequests = null, ?int $perMinutes = null)
    {
        $this->storagePath = $storagePath ?? __DIR__ . '/../../storage/rate_limits';
        $this->maxRequests = $maxRequests ?? ($_ENV['RATE_LIMIT_REQUESTS'] ?? 60);
        $this->perMinutes = $perMinutes ?? ($_ENV['RATE_LIMIT_PER_MINUTE'] ?? 1);
        
        // Ensure storage directory exists
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
    
    /**
     * Process the request and apply rate limiting
     * 
     * @param Request $request The HTTP request
     * @param RequestHandler $handler The request handler
     * @return Response The response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Skip rate limiting if disabled in environment
        if (isset($_ENV['RATE_LIMIT_ENABLED']) && !$_ENV['RATE_LIMIT_ENABLED']) {
            return $handler->handle($request);
        }
        
        // Get client IP address
        $ip = $this->getClientIp($request);
        
        // Check rate limit
        if (!$this->isAllowed($ip)) {
            $response = new SlimResponse(429); // Too Many Requests
            $response->getBody()->write(json_encode([
                'error' => 'Rate limit exceeded. Please try again later.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withHeader('Retry-After', '60');
        }
        
        // Process the request
        return $handler->handle($request);
    }
    
    /**
     * Get client IP address from request
     * 
     * @param Request $request The HTTP request
     * @return string The client IP address
     */
    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();
        
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (isset($serverParams[$header])) {
                $ips = explode(',', $serverParams[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0'; // Default if no valid IP found
    }
    
    /**
     * Check if the client is allowed to make a request
     * 
     * @param string $ip The client IP address
     * @return bool True if allowed, false otherwise
     */
    private function isAllowed(string $ip): bool
    {
        $filename = $this->storagePath . '/' . md5($ip) . '.json';
        
        // If file doesn't exist, create it
        if (!file_exists($filename)) {
            $data = [
                'ip' => $ip,
                'requests' => 0,
                'reset_time' => time() + ($this->perMinutes * 60)
            ];
            file_put_contents($filename, json_encode($data));
        }
        
        // Read current data
        $data = json_decode(file_get_contents($filename), true);
        
        // Check if reset time has passed
        if (time() > $data['reset_time']) {
            $data = [
                'ip' => $ip,
                'requests' => 1,
                'reset_time' => time() + ($this->perMinutes * 60)
            ];
            file_put_contents($filename, json_encode($data));
            return true;
        }
        
        // Check if max requests reached
        if ($data['requests'] >= $this->maxRequests) {
            return false;
        }
        
        // Increment requests
        $data['requests']++;
        file_put_contents($filename, json_encode($data));
        
        return true;
    }
}