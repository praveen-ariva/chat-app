<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class MessageTest extends TestCase
{
    private $client;
    private $baseUri = 'http://localhost:8080';
    private $userId;
    private $groupId;
    
    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ]);
        
        // Create a test user
        $userResponse = $this->client->post('/users', [
            'json' => [
                'username' => 'messagetest' . rand(1000, 9999)
            ]
        ]);
        
        $this->assertEquals(201, $userResponse->getStatusCode(), 'Failed to create test user: ' . $userResponse->getBody());
        
        $userBody = json_decode($userResponse->getBody(), true);
        $this->userId = $userBody['id'];
        
        // Create a test group
        $groupResponse = $this->client->post('/groups', [
            'json' => [
                'name' => 'msggroup' . rand(1000, 9999),
                'user_id' => $this->userId
            ]
        ]);
        
        $this->assertEquals(201, $groupResponse->getStatusCode(), 'Failed to create test group: ' . $groupResponse->getBody());
        
        $groupBody = json_decode($groupResponse->getBody(), true);
        $this->groupId = $groupBody['id'];
    }
    
    public function testSendMessage()
    {
        $response = $this->client->post('/messages', [
            'json' => [
                'user_id' => $this->userId,
                'group_id' => $this->groupId,
                'content' => 'This is a test message'
            ]
        ]);
        
        // Output response for debugging
        echo "Send Message Response: " . $response->getBody() . "\n";
        
        $this->assertEquals(201, $response->getStatusCode(), 'Failed with status ' . $response->getStatusCode() . ': ' . $response->getBody());
        
        $body = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $body);
        $this->assertEquals('This is a test message', $body['content']);
    }
    
    public function testGetGroupMessages()
    {
        // Send a message first
        $messageResponse = $this->client->post('/messages', [
            'json' => [
                'user_id' => $this->userId,
                'group_id' => $this->groupId,
                'content' => 'Test message for retrieval'
            ]
        ]);
        
        $this->assertEquals(201, $messageResponse->getStatusCode(), 'Failed to create test message: ' . $messageResponse->getBody());
        
        // Get messages
        $response = $this->client->get('/groups/' . $this->groupId . '/messages');
        
        // Output response for debugging
        echo "Get Messages Response: " . $response->getBody() . "\n";
        
        $this->assertEquals(200, $response->getStatusCode(), 'Failed with status ' . $response->getStatusCode() . ': ' . $response->getBody());
        
        $body = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('messages', $body);
        $this->assertGreaterThanOrEqual(1, count($body['messages']));
    }
}