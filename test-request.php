<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

// Create a client
$client = new Client([
    'base_uri' => 'http://localhost:8080',
    'http_errors' => false
]);

// Test the user creation endpoint
echo "=== Testing User Creation ===\n";
$response = $client->post('/users', [
    'json' => [
        'username' => 'testuser' . rand(1000, 9999)
    ]
]);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getBody() . "\n\n";

$userData = json_decode($response->getBody(), true);
$userId = $userData['id'] ?? null;

if (!$userId) {
    die("Failed to create test user. Cannot continue tests.\n");
}

// Test the group creation endpoint
echo "=== Testing Group Creation ===\n";
$response = $client->post('/groups', [
    'json' => [
        'name' => 'testgroup' . rand(1000, 9999),
        'user_id' => $userId
    ]
]);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getBody() . "\n\n";

$groupData = json_decode($response->getBody(), true);
$groupId = $groupData['id'] ?? null;

if (!$groupId) {
    die("Failed to create test group. Cannot continue tests.\n");
}

// Test the message creation endpoint
echo "=== Testing Message Sending ===\n";
$response = $client->post('/messages', [
    'json' => [
        'user_id' => $userId,
        'group_id' => $groupId,
        'content' => 'This is a test message'
    ]
]);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getBody() . "\n\n";

// Test getting messages from a group
echo "=== Testing Get Group Messages ===\n";
$response = $client->get('/groups/' . $groupId . '/messages');

echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getBody() . "\n\n";

echo "All tests completed.\n";