<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * User Controller
 * 
 * Handles user-related operations such as creating and retrieving users
 */
class UserController
{
    /**
     * Database connection
     *
     * @var Capsule
     */
    protected $capsule;

    /**
     * Constructor
     *
     * @param Capsule $capsule Database connection manager
     */
    public function __construct(Capsule $capsule)
    {
        $this->capsule = $capsule;
    }
    
    /**
     * Generate a random GUID (UUID v4)
     * 
     * @return string The generated UUID
     */
    private function generateGuid(): string
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

    /**
     * Create a new user
     * 
     * @param Request $request The HTTP request
     * @param Response $response The HTTP response
     * @return Response The HTTP response with created user or error
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (!isset($data['username']) || empty($data['username'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Username is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if username already exists
        $existingUser = $this->capsule->table('users')->where('username', $data['username'])->first();
        if ($existingUser) {
            $response->getBody()->write(json_encode([
                'error' => 'Username already taken'
            ]));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }
        
        // Generate a GUID for the user
        $userId = $this->generateGuid();
        
        // Create user
        $this->capsule->table('users')->insert([
            'id' => $userId,
            'username' => $data['username'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $user = $this->capsule->table('users')->where('id', $userId)->first();
        
        $response->getBody()->write(json_encode([
            'id' => $user->id,
            'username' => $user->username,
            'created_at' => $user->created_at
        ]));
        
        return $response
            ->withStatus(201)
            ->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Get a user by ID
     * 
     * @param Request $request The HTTP request
     * @param Response $response The HTTP response
     * @param array $args The route parameters
     * @return Response The HTTP response with user data or error
     */
    public function get(Request $request, Response $response, array $args): Response
    {
        $user = $this->capsule->table('users')->where('id', $args['id'])->first();
        
        if (!$user) {
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode([
            'id' => $user->id,
            'username' => $user->username,
            'created_at' => $user->created_at
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}