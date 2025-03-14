<?php

namespace App\Controllers;

use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class MessageController
{
    protected $capsule;

    public function __construct(Capsule $capsule)
    {
        $this->capsule = $capsule;
    }
    
    // Send a message to a group
    public function send(Request $request, Response $response): Response
    {
        debug_log('MessageController::send called');
        try {
            $data = $request->getParsedBody();
            debug_log('Request data', $data);
            
            // Validate input
            if (!isset($data['user_id']) || empty($data['user_id'])) {
                debug_log('User ID is required');
                $response->getBody()->write(json_encode([
                    'error' => 'User ID is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            if (!isset($data['group_id']) || empty($data['group_id'])) {
                debug_log('Group ID is required');
                $response->getBody()->write(json_encode([
                    'error' => 'Group ID is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            if (!isset($data['content']) || empty($data['content'])) {
                debug_log('Message content is required');
                $response->getBody()->write(json_encode([
                    'error' => 'Message content is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if user exists
            $user = $this->capsule->table('users')->where('id', $data['user_id'])->first();
            if (!$user) {
                debug_log('User not found', $data['user_id']);
                $response->getBody()->write(json_encode([
                    'error' => 'User not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if group exists
            $group = Group::find($data['group_id']);
            if (!$group) {
                debug_log('Group not found', $data['group_id']);
                $response->getBody()->write(json_encode([
                    'error' => 'Group not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if user is a member of the group
            $isMember = $this->capsule->table('group_members')
                ->where('user_id', $user->id)
                ->where('group_id', $group->id)
                ->exists();
                
            if (!$isMember) {
                debug_log('User is not a member of the group', [
                    'user_id' => $user->id, 
                    'group_id' => $group->id
                ]);
                $response->getBody()->write(json_encode([
                    'error' => 'User is not a member of this group'
                ]));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }
            
            // Create message manually with the correct fields
            debug_log('Creating message', [
                'user_id' => $user->id, 
                'group_id' => $group->id,
                'content' => $data['content']
            ]);
            
            $message = new Message();
            $message->user_id = $user->id;
            $message->group_id = $group->id;
            $message->content = $data['content'];
            $message->created_at = date('Y-m-d H:i:s');
            $message->save();
            
            debug_log('Message created successfully', ['id' => $message->id]);
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
        } catch (\Exception $e) {
            debug_log('Exception in MessageController::send', $e->getMessage());
            debug_log('Exception stack trace', $e->getTraceAsString());
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    // Get all messages from a group
    public function getByGroup(Request $request, Response $response, array $args): Response
    {
        debug_log('MessageController::getByGroup called', ['group_id' => $args['id']]);
        try {
            $groupId = $args['id'];
            
            // Get the user ID from the request (query parameter)
            $queryParams = $request->getQueryParams();
            $userId = $queryParams['user_id'] ?? null;
            
            if (!$userId) {
                debug_log('User ID is required as a query parameter');
                $response->getBody()->write(json_encode([
                    'error' => 'User ID is required as a query parameter'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if user exists
            $user = $this->capsule->table('users')->where('id', $userId)->first();
            if (!$user) {
                debug_log('User not found', $userId);
                $response->getBody()->write(json_encode([
                    'error' => 'User not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if group exists
            $group = Group::find($groupId);
            if (!$group) {
                debug_log('Group not found', $groupId);
                $response->getBody()->write(json_encode([
                    'error' => 'Group not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if user is a member of the group
            $isMember = $this->capsule->table('group_members')
                ->where('user_id', $userId)
                ->where('group_id', $groupId)
                ->exists();
                
            if (!$isMember) {
                debug_log('User is not a member of the group', [
                    'user_id' => $userId, 
                    'group_id' => $groupId
                ]);
                $response->getBody()->write(json_encode([
                    'error' => 'Access denied. Only group members can view messages.'
                ]));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }
            
            // Get all messages from the group
            $messages = $this->capsule->table('messages')
                ->where('group_id', $groupId)
                ->orderBy('created_at', 'asc')
                ->get();
                
            debug_log('Retrieved messages', ['count' => count($messages)]);
            
            // Add user info to each message
            $formattedMessages = [];
            foreach ($messages as $message) {
                $messageUser = $this->capsule->table('users')->where('id', $message->user_id)->first();
                $formattedMessages[] = [
                    'id' => $message->id,
                    'content' => $message->content,
                    'created_at' => $message->created_at,
                    'user' => [
                        'id' => $messageUser->id,
                        'username' => $messageUser->username
                    ]
                ];
            }
            
            $response->getBody()->write(json_encode([
                'group_id' => $groupId,
                'messages' => $formattedMessages
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            debug_log('Exception in MessageController::getByGroup', $e->getMessage());
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}