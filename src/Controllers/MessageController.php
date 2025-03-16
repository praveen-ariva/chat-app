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
    try {
        $data = $request->getParsedBody();
        app_debug('MessageController::send called', $data);
        
        // Validate input
        if (!isset($data['user_id']) || empty($data['user_id'])) {
            app_debug('User ID is required');
            $response->getBody()->write(json_encode([
                'error' => 'User ID is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        if (!isset($data['group_id']) || empty($data['group_id'])) {
            app_debug('Group ID is required');
            $response->getBody()->write(json_encode([
                'error' => 'Group ID is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        if (!isset($data['content']) || empty($data['content'])) {
            app_debug('Message content is required');
            $response->getBody()->write(json_encode([
                'error' => 'Message content is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Make sure IDs are in the correct format
        $userId = (string) $data['user_id'];
        $groupId = (int) $data['group_id'];
        
        // Check if user exists
        $user = $this->capsule->table('users')->where('id', $userId)->first();
        if (!$user) {
            app_debug('User not found', $userId);
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if group exists
        $group = $this->capsule->table('groups')->where('id', $groupId)->first();
        if (!$group) {
            app_debug('Group not found', $groupId);
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
            app_debug('User is not a member of the group', [
                'user_id' => $userId, 
                'group_id' => $groupId
            ]);
            $response->getBody()->write(json_encode([
                'error' => 'User is not a member of this group'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        // Create message directly using query builder
        app_debug('Creating message', [
            'user_id' => $userId, 
            'group_id' => $groupId,
            'content' => $data['content']
        ]);
        
        $content = htmlspecialchars($data['content'], ENT_QUOTES, 'UTF-8');
        
        $messageId = $this->capsule->table('messages')->insertGetId([
            'user_id' => $userId,
            'group_id' => $groupId,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Get the created message
        $message = $this->capsule->table('messages')->where('id', $messageId)->first();
        
        app_debug('Message created successfully', ['id' => $messageId]);
        $response->getBody()->write(json_encode([
            'id' => $messageId,
            'user_id' => $message->user_id,
            'group_id' => $message->group_id,
            'content' => $message->content,
            'created_at' => $message->created_at
        ]));
        
        return $response
            ->withStatus(201)
            ->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        app_error('Exception in MessageController::send: ' . $e->getMessage());
        app_error('Stack trace: ' . $e->getTraceAsString());
        
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
    try {
        // Parse the group ID from route parameters
        $groupId = (int) $args['id']; // Ensure it's an integer
        
        app_debug('MessageController::getByGroup called', ['group_id' => $groupId]);
        
        // Get query parameters
        $queryParams = $request->getQueryParams();
        $userId = $queryParams['user_id'] ?? null;
        
        // Pagination parameters
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 20;
        
        // Validate pagination parameters
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 20;
        if ($limit > 100) $limit = 100; // Set max limit to prevent overload
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        if (!$userId) {
            app_debug('User ID is required as a query parameter');
            $response->getBody()->write(json_encode([
                'error' => 'User ID is required as a query parameter'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Convert user ID to string for UUID format
        $userId = (string) $userId;
        
        // Check if user exists
        $user = $this->capsule->table('users')->where('id', $userId)->first();
        if (!$user) {
            app_debug('User not found', $userId);
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if group exists
        $group = $this->capsule->table('groups')->where('id', $groupId)->first();
        if (!$group) {
            app_debug('Group not found', $groupId);
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
            app_debug('User is not a member of the group', [
                'user_id' => $userId, 
                'group_id' => $groupId
            ]);
            $response->getBody()->write(json_encode([
                'error' => 'Access denied. Only group members can view messages.'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        // Get total message count for pagination info
        $totalMessages = $this->capsule->table('messages')
            ->where('group_id', $groupId)
            ->count();
            
        // Get paginated messages from the group
        $messages = $this->capsule->table('messages')
            ->where('group_id', $groupId)
            ->orderBy('created_at', 'desc') // Latest messages first
            ->offset($offset)
            ->limit($limit)
            ->get();
            
        app_debug('Retrieved messages', ['count' => count($messages)]);
        
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
        
        // Calculate pagination metadata
        $totalPages = ceil($totalMessages / $limit);
        
        $response->getBody()->write(json_encode([
            'group_id' => $groupId,
            'messages' => $formattedMessages,
            'pagination' => [
                'total_messages' => $totalMessages,
                'current_page' => $page,
                'per_page' => $limit,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_previous_page' => $page > 1
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        app_error('Exception in MessageController::getByGroup: ' . $e->getMessage());
        app_error('Stack trace: ' . $e->getTraceAsString());
        
        $response->getBody()->write(json_encode([
            'error' => 'Internal server error',
            'message' => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
}
}