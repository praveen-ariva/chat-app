<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class UserTest extends TestCase
{
    private $client;
    private $baseUri = 'http://localhost:8080';
    
    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'http_errors' => false
        ]);
    }
    
    public function testUserCreation()
    {
        $response = $this->client->post('/users', [
            'json' => [
                'username' => 'testuser' . rand(1000, 9999)
            ]
        ]);
        
        $this->assertEquals(201, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('username', $body);
    }
    
    public function testUserCreationWithoutUsername()
    {
        $response = $this->client->post('/users', [
            'json' => []
        ]);
        
        $this->assertEquals(400, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }
    
    public function testGetExistingUser()
    {
        // First create a user
        $createResponse = $this->client->post('/users', [
            'json' => [
                'username' => 'getuser' . rand(1000, 9999)
            ]
        ]);
        
        $createBody = json_decode($createResponse->getBody(), true);
        $userId = $createBody['id'];
        
        // Now get the user
        $response = $this->client->get('/users/' . $userId);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertEquals($userId, $body['id']);
    }
    
    public function testGetNonExistingUser()
    {
        $response = $this->client->get('/users/9999999');
        
        $this->assertEquals(404, $response->getStatusCode());
    }
}