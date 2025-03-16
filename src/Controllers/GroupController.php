<?php

namespace App\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Utils\LoggerFactory;
use App\Utils\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Group Controller
 * 
 * Handles group-related operations such as creating, joining, and managing groups
 */
class GroupController
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
     * Create a new group
     * 
     * @param Request $request The HTTP request
     * @param Response $response The HTTP response
     * @return Response The HTTP response with created group or error
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            app_debug('Start of GroupController::create');
            $data = $request->getParsedBody();
            app_debug('Request data', $data);
            
            // Validate input
            if (!isset($data['name']) || empty($data['name'])) {
                app_debug('Group name is required');
                $response->getBody()->write(json_encode([
                    'error' => 'Group name is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            if (!isset($data['user_id']) || empty($data['user_id'])) {
                app_debug('User ID is required');
                $response->getBody()->write(json_encode([
                    'error' => 'User ID is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            // Make sure userId is a string for UUID
            $userId = (string) $data['user_id'];
            app_debug('Looking for user with ID', $userId);
            
            // Check if user exists - directly query the DB to avoid model issues
            $user = $this->capsule->table('users')->where('id', $userId)->first();
            if (!$user) {
                app_debug('User not found', $userId);
                $response->getBody()->write(json_encode([
                    'error' => 'User not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Sanitize group name
            $groupName = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
            
            // Check if group name already exists
            $existingGroup = $this->capsule->table('groups')->where('name', $groupName)->first();
            if ($existingGroup) {
                app_debug('Group name already taken', $groupName);
                $response->getBody()->write(json_encode([
                    'error' => 'Group name already taken'
                ]));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }
            
            // Create group - directly use the DB query builder to avoid model issues
            app_debug('Creating group', ['name' => $groupName, 'created_by' => $userId]);
            $groupId = $this->capsule->table('groups')->insertGetId([
                'name' => $groupName,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Add creator as a member
            app_debug('Adding creator as member', ['user_id' => $userId, 'group_id' => $groupId]);
            $this->capsule->table('group_members')->insert([
                'user_id' => $userId,
                'group_id' => $groupId,
                'joined_at' => date('Y-m-d H:i:s')
            ]);
            
            // Get the created group
            $group = $this->capsule->table('groups')->where('id', $groupId)->first();
            
            app_debug('Group created successfully', ['id' => $groupId]);
            $response->getBody()->write(json_encode([
                'id' => $groupId,
                'name' => $group->name,
                'created_by' => $group->created_by,
                'created_at' => $group->created_at
            ]));
            
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            app_error('Exception in GroupController::create: ' . $e->getMessage());
            app_error('Stack trace: ' . $e->getTraceAsString());
            
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    /**
     * Get all groups with optional pagination
     * 
     * @param Request $request The HTTP request
     * @param Response $response The HTTP response
     * @return Response The HTTP response with groups data
     */
    public function getAll(Request $request, Response $response): Response
    {
        try {
            $logger = LoggerFactory::getLogger();
            $logger->debug('GroupController::getAll called');
            
            // Get query parameters for pagination
            $queryParams = $request->getQueryParams();
            $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 20;
            
            // Validate pagination parameters
            if ($page < 1) $page = 1;
            if ($limit < 1) $limit = 20;
            if ($limit > 100) $limit = 100; // Set max limit to prevent overload
            
            // Calculate offset
            $offset = ($page - 1) * $limit;
            
            // Get total group count
            $totalGroups = Group::count();
            
            // Get paginated groups
            $groups = Group::skip($offset)->take($limit)->get(['id', 'name', 'created_by', 'created_at']);
            
            // Calculate pagination metadata
            $totalPages = ceil($totalGroups / $limit);
            
            $logger->debug('Retrieved groups', ['count' => count($groups)]);
            $response->getBody()->write(json_encode([
                'groups' => $groups,
                'pagination' => [
                    'total_groups' => $totalGroups,
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_pages' => $totalPages,
                    'has_next_page' => $page < $totalPages,
                    'has_previous_page' => $page > 1
                ]
            ]));

            return $response;
        } catch (\Exception $e) {
            $logger = LoggerFactory::getLogger();
            $logger->error('Exception in GroupController::getAll', ['message' => $e->getMessage()]);
            
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500);
        }
    }
    
    /**
     * Join a group
     * 
     * @param Request $request The HTTP request
     * @param Response $response The HTTP response
     * @param array $args The route parameters
     * @return Response The HTTP response with join status or error
     */
    public function join(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $request->getParsedBody();
            $groupId = (int) $args['id'];
            
            app_debug('GroupController::join called', [
                'group_id' => $groupId, 
                'data' => $data
            ]);
            
            // Validate input
            if (!isset($data['user_id']) || empty($data['user_id'])) {
                app_debug('User ID is required');
                $response->getBody()->write(json_encode([
                    'error' => 'User ID is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            // Ensure user_id is treated as a string (for UUID)
            $userId = (string) $data['user_id'];
            app_debug('Processing user join', ['user_id' => $userId, 'group_id' => $groupId]);
            
            // Check if user exists using direct query instead of model
            $user = $this->capsule->table('users')->where('id', $userId)->first();
            if (!$user) {
                app_debug('User not found', ['user_id' => $userId]);
                $response->getBody()->write(json_encode([
                    'error' => 'User not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if group exists using direct query
            $group = $this->capsule->table('groups')->where('id', $groupId)->first();
            if (!$group) {
                app_debug('Group not found', ['group_id' => $groupId]);
                $response->getBody()->write(json_encode([
                    'error' => 'Group not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if user is already a member
            $isMember = $this->capsule->table('group_members')
                ->where('user_id', $userId)
                ->where('group_id', $groupId)
                ->exists();
                
            if ($isMember) {
                app_debug('User is already a member', ['user_id' => $userId, 'group_id' => $groupId]);
                $response->getBody()->write(json_encode([
                    'message' => 'User is already a member of this group'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }
            
            // Add user to group - debug the exact values being inserted
            app_debug('Inserting group member record', [
                'user_id' => $userId, 
                'group_id' => $groupId,
                'user_id_type' => gettype($userId),
                'group_id_type' => gettype($groupId)
            ]);
            
            // Explicitly define the data to insert to ensure correct types
            $memberData = [
                'user_id' => $userId,  // This should be a string UUID
                'group_id' => $groupId, // This should be an integer
                'joined_at' => date('Y-m-d H:i:s')
            ];
            
            $this->capsule->table('group_members')->insert($memberData);
            
            // Verify that the member was added correctly
            $memberCheck = $this->capsule->table('group_members')
                ->where('user_id', $userId)
                ->where('group_id', $groupId)
                ->first();
                
            app_debug('Member record after insertion', $memberCheck);
            
            $response->getBody()->write(json_encode([
                'message' => 'User joined the group successfully',
                'user_id' => $userId,
                'group_id' => $groupId
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            app_error('Exception in GroupController::join: ' . $e->getMessage());
            app_error('Stack trace: ' . $e->getTraceAsString());
            
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    /**
     * Remove a user from a group (owner only)
     * 
     * @param Request $request The HTTP request
     * @param Response $response The HTTP response
     * @param array $args The route parameters
     * @return Response The HTTP response with removal status or error
     */
    public function removeUser(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $request->getParsedBody();
            $groupId = (int) $args['id']; // Ensure it's an integer
            
            app_debug('GroupController::removeUser called', [
                'group_id' => $groupId, 
                'request_data' => $data
            ]);
            
            // Validate input
            if (!isset($data['user_id']) || empty($data['user_id'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'User ID to remove is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            if (!isset($data['owner_id']) || empty($data['owner_id'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'Owner ID is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            // Ensure user_id and owner_id are treated as strings (for UUID)
            $userIdToRemove = (string) $data['user_id'];
            $ownerId = (string) $data['owner_id'];
            
            app_debug('Processing user removal', [
                'user_id_to_remove' => $userIdToRemove, 
                'owner_id' => $ownerId,
                'group_id' => $groupId
            ]);
            
            // Check if the users exist
            $ownerExists = $this->capsule->table('users')->where('id', $ownerId)->exists();
            if (!$ownerExists) {
                app_debug('Owner user not found', ['owner_id' => $ownerId]);
                $response->getBody()->write(json_encode([
                    'error' => 'Owner user not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            $userToRemoveExists = $this->capsule->table('users')->where('id', $userIdToRemove)->exists();
            if (!$userToRemoveExists) {
                app_debug('User to remove not found', ['user_id' => $userIdToRemove]);
                $response->getBody()->write(json_encode([
                    'error' => 'User to remove not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if group exists - use direct query
            $group = $this->capsule->table('groups')->where('id', $groupId)->first();
            if (!$group) {
                app_debug('Group not found', ['group_id' => $groupId]);
                $response->getBody()->write(json_encode([
                    'error' => 'Group not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Verify that the requester is the group owner
            app_debug('Checking ownership', ['group_created_by' => $group->created_by, 'owner_id' => $ownerId]);
            if ($group->created_by !== $ownerId) {
                app_debug('Permission denied - not group owner', [
                    'owner_id' => $ownerId, 
                    'actual_owner' => $group->created_by
                ]);
                $response->getBody()->write(json_encode([
                    'error' => 'Only the group owner can remove users'
                ]));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }
            
            // Check if user exists and is a member - debug the query
            $memberRecord = $this->capsule->table('group_members')
                ->where('user_id', $userIdToRemove)
                ->where('group_id', $groupId)
                ->first();
                
            app_debug('Member record check', $memberRecord);
            
            if (!$memberRecord) {
                app_debug('User is not a member of this group', [
                    'user_id' => $userIdToRemove, 
                    'group_id' => $groupId
                ]);
                $response->getBody()->write(json_encode([
                    'error' => 'User is not a member of this group'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Cannot remove the owner from the group
            if ($userIdToRemove === $group->created_by) {
                app_debug('Cannot remove group owner from the group', [
                    'user_id' => $userIdToRemove, 
                    'group_id' => $groupId
                ]);
                $response->getBody()->write(json_encode([
                    'error' => 'Cannot remove the group owner from the group'
                ]));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }
            
            // Remove user from group - use exact values for comparison
            app_debug('Removing user from group', [
                'user_id' => $userIdToRemove, 
                'group_id' => $groupId
            ]);
            
            $removed = $this->capsule->table('group_members')
                ->where('user_id', '=', $userIdToRemove)
                ->where('group_id', '=', $groupId)
                ->delete();
                
            app_debug('Removal result', ['rows_affected' => $removed]);
                
            if ($removed) {
                app_debug('User removed from group successfully', [
                    'user_id' => $userIdToRemove, 
                    'group_id' => $groupId
                ]);
                $response->getBody()->write(json_encode([
                    'message' => 'User removed from the group successfully'
                ]));
                
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                app_error('Failed to remove user from group', [
                    'user_id' => $userIdToRemove, 
                    'group_id' => $groupId
                ]);
                $response->getBody()->write(json_encode([
                    'error' => 'Failed to remove user from group'
                ]));
                
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
            app_error('Exception in GroupController::removeUser: ' . $e->getMessage());
            app_error('Stack trace: ' . $e->getTraceAsString());
            
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    /**
     * Delete a group (owner only)
     * 
     * @param Request $request The HTTP request
     * @param Response $response The HTTP response
     * @param array $args The route parameters
     * @return Response The HTTP response with deletion status or error
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $request->getParsedBody();
            // Parse the group ID from route parameters
            $groupId = (int) $args['id']; // Ensure it's an integer
            
            app_debug('GroupController::delete called', [
                'group_id' => $groupId, 
                'request_data' => $data
            ]);
            
            // Validate input
            if (!isset($data['user_id']) || empty($data['user_id'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'User ID is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            // Convert ID to string for UUID format
            $userId = (string) $data['user_id'];
            
            // Check if group exists - use direct query
            $group = $this->capsule->table('groups')->where('id', $groupId)->first();
            if (!$group) {
                app_debug('Group not found', ['group_id' => $groupId]);
                $response->getBody()->write(json_encode([
                    'error' => 'Group not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Verify that the requester is the group owner
            if ($group->created_by !== $userId) {
                app_debug('Permission denied - not group owner', [
                    'user_id' => $userId, 
                    'actual_owner' => $group->created_by
                ]);
                $response->getBody()->write(json_encode([
                    'error' => 'Only the group owner can delete the group'
                ]));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }
            
            // Delete all messages in the group
            app_debug('Deleting messages in group', ['group_id' => $groupId]);
            $this->capsule->table('messages')
                ->where('group_id', $groupId)
                ->delete();
            
            // Delete all group memberships
            app_debug('Deleting group memberships', ['group_id' => $groupId]);
            $this->capsule->table('group_members')
                ->where('group_id', $groupId)
                ->delete();
            
            // Delete the group
            app_debug('Deleting group', ['group_id' => $groupId]);
            $deleted = $this->capsule->table('groups')
                ->where('id', $groupId)
                ->delete();
                
            if ($deleted) {
                app_debug('Group deleted successfully', ['group_id' => $groupId]);
                $response->getBody()->write(json_encode([
                    'message' => 'Group deleted successfully'
                ]));
                
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                app_error('Failed to delete group', ['group_id' => $groupId]);
                $response->getBody()->write(json_encode([
                    'error' => 'Failed to delete group'
                ]));
                
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
            app_error('Exception in GroupController::delete: ' . $e->getMessage());
            app_error('Stack trace: ' . $e->getTraceAsString());
            
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}