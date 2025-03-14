<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

// Create a client
$client = new Client([
    'base_uri' => 'http://localhost:8080',
    'http_errors' => false
]);

// Test the user creation endpoint (should return GUID now)
echo "=== Testing User Creation with GUID ===\n";
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

echo "User created with GUID: " . $userId . "\n\n";

// Create another user (non-member)
$response = $client->post('/users', [
    'json' => [
        'username' => 'nonmember' . rand(1000, 9999)
    ]
]);

$nonMemberData = json_decode($response->getBody(), true);
$nonMemberId = $nonMemberData['id'] ?? null;

if (!$nonMemberId) {
    die("Failed to create non-member test user. Cannot continue tests.\n");
}

echo "Non-member user created with GUID: " . $nonMemberId . "\n\n";

// Test the group creation endpoint
echo "=== Testing Group Creation with GUID user ===\n";
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
echo "=== Testing Message Sending with GUID user ===\n";
$response = $client->post('/messages', [
    'json' => [
        'user_id' => $userId,
        'group_id' => $groupId,
        'content' => 'This is a test message'
    ]
]);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getBody() . "\n\n";

// Test getting messages from a group as a member
echo "=== Testing Get Group Messages as a Member ===\n";
$response = $client->get('/groups/' . $groupId . '/messages?user_id=' . $userId);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getBody() . "\n\n";

// Test getting messages from a group as a non-member (should be rejected)
echo "=== Testing Get Group Messages as a Non-Member (should fail) ===\n";
$response = $client->get('/groups/' . $groupId . '/messages?user_id=' . $nonMemberId);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getBody() . "\n\n";

echo "All tests completed.\n";