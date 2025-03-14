<?php

namespace App\Controllers;

use App\Models\Group;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class GroupController
{
    protected $capsule;

    public function __construct(Capsule $capsule)
    {
        $this->capsule = $capsule;
    }
    
    // Create a new group
    public function create(Request $request, Response $response): Response
    {
        debug_log('GroupController::create called');
        try {
            $data = $request->getParsedBody();
            debug_log('Request data', $data);
            
            // Validate input
            if (!isset($data['name']) || empty($data['name'])) {
                debug_log('Group name is required');
                $response->getBody()->write(json_encode([
                    'error' => 'Group name is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            if (!isset($data['user_id']) || empty($data['user_id'])) {
                debug_log('User ID is required');
                $response->getBody()->write(json_encode([
                    'error' => 'User ID is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if user exists
            $user = User::find($data['user_id']);
            if (!$user) {
                debug_log('User not found', $data['user_id']);
                $response->getBody()->write(json_encode([
                    'error' => 'User not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if group name already exists
            $existingGroup = Group::where('name', $data['name'])->first();
            if ($existingGroup) {
                debug_log('Group name already taken', $data['name']);
                $response->getBody()->write(json_encode([
                    'error' => 'Group name already taken'
                ]));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }
            
            // Create group manually with the correct fields
            debug_log('Creating group', ['name' => $data['name'], 'created_by' => $user->id]);
            $group = new Group();
            $group->name = $data['name'];
            $group->created_by = $user->id;
            $group->created_at = date('Y-m-d H:i:s');
            $group->save();
            
            // Add creator as a member
            debug_log('Adding creator as member', ['user_id' => $user->id, 'group_id' => $group->id]);
            $this->capsule->table('group_members')->insert([
                'user_id' => $user->id,
                'group_id' => $group->id,
                'joined_at' => date('Y-m-d H:i:s')
            ]);
            
            debug_log('Group created successfully', ['id' => $group->id]);
            $response->getBody()->write(json_encode([
                'id' => $group->id,
                'name' => $group->name,
                'created_by' => $group->created_by,
                'created_at' => $group->created_at
            ]));
            
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            debug_log('Exception in GroupController::create', $e->getMessage());
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    // Other methods remain the same...
    public function getAll(Request $request, Response $response): Response
    {
        debug_log('GroupController::getAll called');
        try {
            $groups = Group::all(['id', 'name', 'created_by', 'created_at']);
            debug_log('Retrieved groups', ['count' => count($groups)]);
            
            $response->getBody()->write(json_encode([
                'groups' => $groups
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            debug_log('Exception in GroupController::getAll', $e->getMessage());
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    public function join(Request $request, Response $response, array $args): Response
    {
        debug_log('GroupController::join called', ['group_id' => $args['id']]);
        try {
            $data = $request->getParsedBody();
            debug_log('Request data', $data);
            $groupId = $args['id'];
            
            // Validate input
            if (!isset($data['user_id']) || empty($data['user_id'])) {
                debug_log('User ID is required');
                $response->getBody()->write(json_encode([
                    'error' => 'User ID is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if user exists
            $user = User::find($data['user_id']);
            if (!$user) {
                debug_log('User not found', $data['user_id']);
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
            
            // Check if user is already a member
            $isMember = $this->capsule->table('group_members')
                ->where('user_id', $user->id)
                ->where('group_id', $group->id)
                ->exists();
                
            if ($isMember) {
                debug_log('User is already a member', ['user_id' => $user->id, 'group_id' => $group->id]);
                $response->getBody()->write(json_encode([
                    'message' => 'User is already a member of this group'
                ]));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
            }
            
            // Add user to group
            debug_log('Adding user to group', ['user_id' => $user->id, 'group_id' => $group->id]);
            $this->capsule->table('group_members')->insert([
                'user_id' => $user->id,
                'group_id' => $group->id,
                'joined_at' => date('Y-m-d H:i:s')
            ]);
            
            debug_log('User joined group successfully');
            $response->getBody()->write(json_encode([
                'message' => 'User joined the group successfully'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            debug_log('Exception in GroupController::join', $e->getMessage());
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}