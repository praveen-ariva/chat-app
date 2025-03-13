<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class UserController
{
    protected $capsule;

    public function __construct(Capsule $capsule)
    {
        $this->capsule = $capsule;
    }

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
        
        // Create user
        $userId = $this->capsule->table('users')->insertGetId([
            'username' => $data['username'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $user = $this->capsule->table('users')->find($userId);
        
        $response->getBody()->write(json_encode([
            'id' => $user->id,
            'username' => $user->username,
            'created_at' => $user->created_at
        ]));
        
        return $response
            ->withStatus(201)
            ->withHeader('Content-Type', 'application/json');
    }
    
    public function get(Request $request, Response $response, array $args): Response
    {
        $user = $this->capsule->table('users')->find($args['id']);
        
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