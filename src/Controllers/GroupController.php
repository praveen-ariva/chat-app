<?php

namespace App\Controllers;

use App\Models\Group;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GroupController
{
    // Create a new group
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (!isset($data['name']) || empty($data['name'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Group name is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        if (!isset($data['user_id']) || empty($data['user_id'])) {
            $response->getBody()->write(json_encode([
                'error' => 'User ID is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if user exists
        $user = User::find($data['user_id']);
        if (!$user) {
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if group name already exists
        $existingGroup = Group::where('name', $data['name'])->first();
        if ($existingGroup) {
            $response->getBody()->write(json_encode([
                'error' => 'Group name already taken'
            ]));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }
        
        // Create group
        $group = Group::create([
            'name' => $data['name'],
            'created_by' => $user->id
        ]);
        
        // Add creator as a member
        $group->members()->attach($user->id);
        
        $response->getBody()->write(json_encode([
            'id' => $group->id,
            'name' => $group->name,
            'created_by' => $group->created_by,
            'created_at' => $group->created_at
        ]));
        
        return $response
            ->withStatus(201)
            ->withHeader('Content-Type', 'application/json');
    }
    
    // Get all groups
    public function getAll(Request $request, Response $response): Response
    {
        $groups = Group::all(['id', 'name', 'created_by', 'created_at']);
        
        $response->getBody()->write(json_encode([
            'groups' => $groups
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    // Join a group
    public function join(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $groupId = $args['id'];
        
        // Validate input
        if (!isset($data['user_id']) || empty($data['user_id'])) {
            $response->getBody()->write(json_encode([
                'error' => 'User ID is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if user exists
        $user = User::find($data['user_id']);
        if (!$user) {
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if group exists
        $group = Group::find($groupId);
        if (!$group) {
            $response->getBody()->write(json_encode([
                'error' => 'Group not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if user is already a member
        if ($group->members()->where('user_id', $user->id)->exists()) {
            $response->getBody()->write(json_encode([
                'message' => 'User is already a member of this group'
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }
        
        // Add user to group
        $group->members()->attach($user->id);
        
        $response->getBody()->write(json_encode([
            'message' => 'User joined the group successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}