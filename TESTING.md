# Chat API Testing Guide

This guide covers how to thoroughly test all endpoints of your Chat API using various methods.

## Prerequisites

Before running the tests, make sure:

1. Your PHP server is running:
   ```bash
   php -S localhost:8080 -t public
   ```

2. Your database is properly initialized:
   ```bash
   cat database/schema.sql | sqlite3 database/chat.sqlite
   ```

## Testing Methods

You have several options for testing the API:

### 1. Using PHP API Tester Script

The PHP tester script (`api_tester.php`) provides comprehensive testing of all endpoints with detailed output.

```bash
php api_tester.php
```

### 2. Using Bash/Curl Script

The shell script (`api_test.sh`) uses curl to test all endpoints sequentially.

```bash
# Make the script executable
chmod +x api_test.sh

# Run the tests
./api_test.sh
```

### 3. Using Postman

1. Import the provided Postman collection (`Chat API Tests.postman_collection.json`)
2. Set the `baseUrl` variable to `http://localhost:8080` in the collection variables
3. Run the entire collection or individual requests

### 4. Using PHPUnit for Unit Tests

The unit tests focus on testing each controller independently without making HTTP requests.

```bash
# Run all tests
vendor/bin/phpunit tests/

# Run specific controller tests
vendor/bin/phpunit tests/UserControllerTest.php
vendor/bin/phpunit tests/GroupControllerTest.php
vendor/bin/phpunit tests/MessageControllerTest.php
```

## API Endpoints Overview

### User API
- `POST /users` - Create a new user
- `GET /users/{id}` - Get user by ID

### Group API
- `POST /groups` - Create a new group
- `GET /groups` - Get all groups
- `POST /groups/{id}/join` - Join a group

### Message API
- `POST /messages` - Send a message
- `GET /groups/{id}/messages` - Get all messages in a group

## Common Test Scenarios

Each endpoint should be tested with:

1. Valid inputs (happy path)
2. Missing required fields
3. Invalid field values
4. Non-existent resources
5. Unauthorized access (e.g., sending messages to groups you're not a member of)

## Troubleshooting

If tests are failing, check:

1. Server logs for PHP errors
2. Database schema for correct table structure
3. Routes configuration
4. Model definitions, especially the `$timestamps = false;` setting
5. Controller error handling

## Extending the Tests

When adding new features to your API, make sure to:

1. Add corresponding test cases to the PHP and bash test scripts
2. Create new Postman requests for the new endpoints
3. Update the unit tests

This comprehensive testing approach will ensure your Chat API remains reliable as you continue to develop it.