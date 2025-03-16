<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Illuminate\Database\Capsule\Manager as Capsule;

class AuthMiddleware implements MiddlewareInterface
{
    protected $capsule;
    
    public function __construct(Capsule $capsule)
    {
        $this->capsule = $capsule;
    }
    
    /**
     * Check if user exists in the database
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $userId = null;
        
        // First check query parameters
        $params = $request->getQueryParams();
        if (isset($params['user_id'])) {
            $userId = $params['user_id'];
        }
        
        // Then check request body
        if (!$userId) {
            $body = $request->getParsedBody();
            if (isset($body['user_id'])) {
                $userId = $body['user_id'];
            }
        }
        
        if ($userId) {
            $user = $this->capsule->table('users')->where('id', $userId)->first();
            
            if (!$user) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode([
                    'error' => 'Invalid user ID'
                ]));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
            
            // Add user to request attributes for controllers to access
            $request = $request->withAttribute('user', $user);
        }
        
        return $handler->handle($request);
    }
}