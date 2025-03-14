<?php
/**
 * Chat API Tester Script
 * 
 * This script tests all the endpoints of the Chat API according to the test plan.
 * It makes HTTP requests to each endpoint and validates the responses.
 */

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ApiTester
{
    private $client;
    private $baseUrl;
    private $testUsers = [];
    private $testGroups = [];
    private $testMessages = [];

    public function __construct($baseUrl = 'http://localhost:8080')
    {
        $this->baseUrl = $baseUrl;
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'http_errors' => false
        ]);
    }

    public function runAllTests()
    {
        echo "Starting Chat API Tests\n";
        echo "======================\n\n";

        $this->testUserApi();
        $this->testGroupApi();
        $this->testMessageApi();

        echo "\nAll tests completed!\n";
    }

    private function testUserApi()
    {
        echo "Testing User API\n";
        echo "--------------\n";

        // Test: Create new user
        echo "Test: Create new user... ";
        $response = $this->client->post('/users', [
            'json' => ['username' => 'testuser' . rand(1000, 9999)]
        ]);
        $body = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 201 && isset($body['id'])) {
            echo "PASSED ✓\n";
            $this->testUsers[] = $body;
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Test: Create user with existing username
        if (!empty($this->testUsers)) {
            echo "Test: Create user with existing username... ";
            $response = $this->client->post('/users', [
                'json' => ['username' => $this->testUsers[0]['username']]
            ]);
            
            if ($response->getStatusCode() === 409) {
                echo "PASSED ✓\n";
            } else {
                echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
                echo "Response: " . $response->getBody() . "\n";
            }
        }

        // Test: Create user with missing username
        echo "Test: Create user with missing username... ";
        $response = $this->client->post('/users', [
            'json' => []
        ]);
        
        if ($response->getStatusCode() === 400) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Test: Get existing user
        if (!empty($this->testUsers)) {
            echo "Test: Get existing user... ";
            $userId = $this->testUsers[0]['id'];
            $response = $this->client->get("/users/{$userId}");
            $body = json_decode($response->getBody(), true);
            
            if ($response->getStatusCode() === 200 && $body['id'] === $userId) {
                echo "PASSED ✓\n";
            } else {
                echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
                echo "Response: " . $response->getBody() . "\n";
            }
        }

        // Test: Get non-existing user
        echo "Test: Get non-existing user... ";
        $response = $this->client->get("/users/9999");
        
        if ($response->getStatusCode() === 404) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        echo "\n";
    }

    private function testGroupApi()
    {
        echo "Testing Group API\n";
        echo "----------------\n";

        if (empty($this->testUsers)) {
            echo "Cannot test Group API: No test users available. Please fix User API tests first.\n\n";
            return;
        }

        $userId = $this->testUsers[0]['id'];
        
        // Test: Create new group
        echo "Test: Create new group... ";
        $groupName = 'testgroup' . rand(1000, 9999);
        $response = $this->client->post('/groups', [
            'json' => [
                'name' => $groupName,
                'user_id' => $userId
            ]
        ]);
        $body = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 201 && isset($body['id'])) {
            echo "PASSED ✓\n";
            $this->testGroups[] = $body;
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Test: Create group with existing name
        if (!empty($this->testGroups)) {
            echo "Test: Create group with existing name... ";
            $response = $this->client->post('/groups', [
                'json' => [
                    'name' => $this->testGroups[0]['name'],
                    'user_id' => $userId
                ]
            ]);
            
            if ($response->getStatusCode() === 409) {
                echo "PASSED ✓\n";
            } else {
                echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
                echo "Response: " . $response->getBody() . "\n";
            }
        }

        // Test: Create group with missing name
        echo "Test: Create group with missing name... ";
        $response = $this->client->post('/groups', [
            'json' => [
                'user_id' => $userId
            ]
        ]);
        
        if ($response->getStatusCode() === 400) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Test: Create group with missing user_id
        echo "Test: Create group with missing user_id... ";
        $response = $this->client->post('/groups', [
            'json' => [
                'name' => 'testgroup' . rand(1000, 9999)
            ]
        ]);
        
        if ($response->getStatusCode() === 400) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Test: Get all groups
        echo "Test: Get all groups... ";
        $response = $this->client->get('/groups');
        $body = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 200 && isset($body['groups'])) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Create a second user for join tests
        $response = $this->client->post('/users', [
            'json' => ['username' => 'joinuser' . rand(1000, 9999)]
        ]);
        $secondUser = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 201 && isset($secondUser['id'])) {
            $this->testUsers[] = $secondUser;
            
            // Test: Join a group
            if (!empty($this->testGroups)) {
                echo "Test: Join a group... ";
                $groupId = $this->testGroups[0]['id'];
                $response = $this->client->post("/groups/{$groupId}/join", [
                    'json' => [
                        'user_id' => $secondUser['id']
                    ]
                ]);
                
                if ($response->getStatusCode() === 200) {
                    echo "PASSED ✓\n";
                } else {
                    echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
                    echo "Response: " . $response->getBody() . "\n";
                }
            }
        }

        // Test: Join a non-existing group
        echo "Test: Join a non-existing group... ";
        $response = $this->client->post("/groups/9999/join", [
            'json' => [
                'user_id' => $userId
            ]
        ]);
        
        if ($response->getStatusCode() === 404) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        echo "\n";
    }

    private function testMessageApi()
    {
        echo "Testing Message API\n";
        echo "------------------\n";

        if (empty($this->testUsers) || empty($this->testGroups)) {
            echo "Cannot test Message API: No test users or groups available. Please fix previous tests first.\n\n";
            return;
        }

        $userId = $this->testUsers[0]['id'];
        $groupId = $this->testGroups[0]['id'];
        
        // Test: Send a valid message
        echo "Test: Send a valid message... ";
        $response = $this->client->post('/messages', [
            'json' => [
                'user_id' => $userId,
                'group_id' => $groupId,
                'content' => 'Test message ' . rand(1000, 9999)
            ]
        ]);
        $body = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 201 && isset($body['id'])) {
            echo "PASSED ✓\n";
            $this->testMessages[] = $body;
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Test: Send with missing user_id
        echo "Test: Send with missing user_id... ";
        $response = $this->client->post('/messages', [
            'json' => [
                'group_id' => $groupId,
                'content' => 'Test message'
            ]
        ]);
        
        if ($response->getStatusCode() === 400) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Test: Send with missing group_id
        echo "Test: Send with missing group_id... ";
        $response = $this->client->post('/messages', [
            'json' => [
                'user_id' => $userId,
                'content' => 'Test message'
            ]
        ]);
        
        if ($response->getStatusCode() === 400) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Test: Send with missing content
        echo "Test: Send with missing content... ";
        $response = $this->client->post('/messages', [
            'json' => [
                'user_id' => $userId,
                'group_id' => $groupId
            ]
        ]);
        
        if ($response->getStatusCode() === 400) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Test: Get messages from a group
        echo "Test: Get messages from a group... ";
        $response = $this->client->get("/groups/{$groupId}/messages");
        $body = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 200 && isset($body['messages'])) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        // Test: Get messages from a non-existing group
        echo "Test: Get messages from a non-existing group... ";
        $response = $this->client->get("/groups/9999/messages");
        
        if ($response->getStatusCode() === 404) {
            echo "PASSED ✓\n";
        } else {
            echo "FAILED ✗ (Status: {$response->getStatusCode()})\n";
            echo "Response: " . $response->getBody() . "\n";
        }

        echo "\n";
    }
}

// Run the tests
$tester = new ApiTester();
$tester->runAllTests();