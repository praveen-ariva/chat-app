<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class GroupTest extends TestCase
{
    private $client;
    private $baseUri = 'http://localhost:8080';
    private $userId;
    
    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'http_errors' => false
        ]);
        
        // Create a test user for our tests
        $response = $this->client->post('/users', [
            'json' => [
                'username' => 'grouptest' . rand(1000, 9999)
            ]
        ]);
        
        $body = json_decode($response->getBody(), true);
        $this->userId = $body['id'];
    }
    
    public function testGroupCreation()
    {
        $groupName = 'testgroup' . rand(1000, 9999);
        
        $response = $this->client->post('/groups', [
            'json' => [
                'name' => $groupName,
                'user_id' => $this->userId
            ]
        ]);
        
        $this->assertEquals(201, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $body);
        $this->assertEquals($groupName, $body['name']);
        $this->assertEquals($this->userId, $body['created_by']);
    }
    
    public function testGetAllGroups()
    {
        $response = $this->client->get('/groups');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('groups', $body);
    }
    
    public function testJoinGroup()
    {
        // First create a group
        $groupName = 'joingroup' . rand(1000, 9999);
        $createResponse = $this->client->post('/groups', [
            'json' => [
                'name' => $groupName,
                'user_id' => $this->userId
            ]
        ]);
        
        $createBody = json_decode($createResponse->getBody(), true);
        $groupId = $createBody['id'];
        
        // Create another user
        $userResponse = $this->client->post('/users', [
            'json' => [
                'username' => 'joiner' . rand(1000, 9999)
            ]
        ]);
        
        $userBody = json_decode($userResponse->getBody(), true);
        $joinerUserId = $userBody['id'];
        
        // Now join the group
        $response = $this->client->post('/groups/' . $groupId . '/join', [
            'json' => [
                'user_id' => $joinerUserId
            ]
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
    }
}