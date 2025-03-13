<?php

namespace App\Controllers;

use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MessageController
{
    // Send a message to a group
    public function send(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (!isset($data['user_id']) || empty($data['user_id'])) {
            $response->getBody()->write(json_encode([
                'error' => 'User ID is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        if (!isset($data['group_id']) || empty($data['group_id'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Group ID is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        if (!isset($data['content']) || empty($data['content'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Message content is required'
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
        $group = Group::find($data['group_id']);
        if (!$group) {
            $response->getBody()->write(json_encode([
                'error' => 'Group not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if user is a member of the group
        if (!$group->members()->where('user_id', $user->id)->exists()) {
            $response->getBody()->write(json_encode([
                'error' => 'User is not a member of this group'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        // Create message
        $message = Message::create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'content' => $data['content']
        ]);
        
        $response->getBody()->write(json_encode([
            'id' => $message->id,
            'user_id' => $message->user_id,
            'group_id' => $message->group_id,
            'content' => $message->content,
            'created_at' => $message->created_at
        ]));
        
        return $response
            ->withStatus(201)
            ->withHeader('Content-Type', 'application/json');
    }
    
    // Get all messages from a group
    public function getByGroup(Request $request, Response $response, array $args): Response
    {
        $groupId = $args['id'];
        
        // Check if group exists
        $group = Group::find($groupId);
        if (!$group) {
            $response->getBody()->write(json_encode([
                'error' => 'Group not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Get all messages from the group with user info
        $messages = Message::where('group_id', $groupId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'created_at' => $message->created_at,
                    'user' => [
                        'id' => $message->user->id,
                        'username' => $message->user->username
                    ]
                ];
            });
        
        $response->getBody()->write(json_encode([
            'group_id' => $groupId,
            'messages' => $messages
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}