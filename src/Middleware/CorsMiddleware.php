<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * CORS Middleware
 * 
 * Handles Cross-Origin Resource Sharing (CORS) headers
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Process the request and add CORS headers to the response
     * 
     * @param Request $request The HTTP request
     * @param RequestHandler $handler The request handler
     * @return Response The response with CORS headers
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
}