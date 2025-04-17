# Chat Application Backend

A robust RESTful chat application backend built with PHP and the Slim framework.

## Features

- **User Management**: Create and retrieve users with UUIDs
- **Group Management**: Create, list, join, and delete groups
- **Messaging**: Send and retrieve messages within groups
- **Group Owner Permissions**: Special permissions for group owners
- **Authentication**: Simple token-based authentication for API access
- **Rate Limiting**: Protection against API abuse
- **Pagination**: Efficient handling of large datasets
- **CORS Support**: Cross-Origin Resource Sharing for frontend integration
- **Input Validation & Sanitization**: Protection against common security vulnerabilities

## Technical Stack

- **PHP 8.0+**: Modern PHP language features
- **Slim Framework**: Lightweight PHP framework for routing and middleware
- **Eloquent ORM**: Database abstraction and model relationships
- **SQLite**: Simple, fast, and file-based database
- **PHPUnit**: Comprehensive testing

## Requirements

- PHP 8.0 or higher
- Composer
- SQLite3

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   cd chat-app
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Set up environment:
   ```
   cp .env.example .env
   ```

4. Initialize the database:
   ```
   mkdir -p database
   touch database/chat.sqlite
   cat database/schema.sql | sqlite3 database/chat.sqlite
   ```

5. Create required directories:
   ```
   mkdir -p logs
   mkdir -p storage/rate_limits
   ```

6. Set permissions for directories:
   ```
   chmod -R 755 logs storage
   ```

7. Start the development server:
   ```
   php -S localhost:8080 -t public
   ```

## Testing the API

You can test the API using the provided test scripts, curl commands, or any API client like Postman.

### Using curl

#### 1. Create a User

```bash
curl -X POST http://localhost:8080/users \
  -H "Content-Type: application/json" \
  -d '{"username": "testuser1"}'
```

Response:
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "username": "testuser1",
  "created_at": "2023-03-16 12:34:56"
}
```

Note: Save the user ID for subsequent requests.

#### 2. Create a Group

```bash
curl -X POST http://localhost:8080/groups \
  -H "Content-Type: application/json" \
  -d '{"name": "testgroup", "user_id": "550e8400-e29b-41d4-a716-446655440000"}'
```

Response:
```json
{
  "id": 1,
  "name": "testgroup",
  "created_by": "550e8400-e29b-41d4-a716-446655440000",
  "created_at": "2023-03-16 12:35:22"
}
```

#### 3. Create a Second User

```bash
curl -X POST http://localhost:8080/users \
  -H "Content-Type: application/json" \
  -d '{"username": "testuser2"}'
```

Response:
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440001",
  "username": "testuser2",
  "created_at": "2023-03-16 12:36:10"
}
```

#### 4. Join the Group with Second User

```bash
curl -X POST http://localhost:8080/groups/1/join \
  -H "Content-Type: application/json" \
  -d '{"user_id": "550e8400-e29b-41d4-a716-446655440001"}'
```

Response:
```json
{
  "message": "User joined the group successfully",
  "user_id": "550e8400-e29b-41d4-a716-446655440001",
  "group_id": 1
}
```

#### 5. List All Groups

```bash
curl http://localhost:8080/groups
```

Response:
```json
{
  "groups": [
    {
      "id": 1,
      "name": "testgroup",
      "created_by": "550e8400-e29b-41d4-a716-446655440000",
      "created_at": "2023-03-16 12:35:22"
    }
  ],
  "pagination": {
    "total_groups": 1,
    "current_page": 1,
    "per_page": 20,
    "total_pages": 1,
    "has_next_page": false,
    "has_previous_page": false
  }
}
```

#### 6. Send a Message from the First User

```bash
curl -X POST http://localhost:8080/messages \
  -H "Content-Type: application/json" \
  -d '{"user_id": "550e8400-e29b-41d4-a716-446655440000", "group_id": 1, "content": "Hello, this is a test message!"}'
```

Response:
```json
{
  "id": 1,
  "user_id": "550e8400-e29b-41d4-a716-446655440000",
  "group_id": 1,
  "content": "Hello, this is a test message!",
  "created_at": "2023-03-16 12:37:45"
}
```

#### 7. Send a Message from the Second User

```bash
curl -X POST http://localhost:8080/messages \
  -H "Content-Type: application/json" \
  -d '{"user_id": "550e8400-e29b-41d4-a716-446655440001", "group_id": 1, "content": "Hi! I received your message."}'
```

Response:
```json
{
  "id": 2,
  "user_id": "550e8400-e29b-41d4-a716-446655440001",
  "group_id": 1,
  "content": "Hi! I received your message.",
  "created_at": "2023-03-16 12:38:22"
}
```

#### 8. Get Messages from Group

```bash
curl "http://localhost:8080/groups/1/messages?user_id=550e8400-e29b-41d4-a716-446655440000"
```

Response:
```json
{
  "group_id": 1,
  "messages": [
    {
      "id": 2,
      "content": "Hi! I received your message.",
      "created_at": "2023-03-16 12:38:22",
      "user": {
        "id": "550e8400-e29b-41d4-a716-446655440001",
        "username": "testuser2"
      }
    },
    {
      "id": 1,
      "content": "Hello, this is a test message!",
      "created_at": "2023-03-16 12:37:45",
      "user": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "username": "testuser1"
      }
    }
  ],
  "pagination": {
    "total_messages": 2,
    "current_page": 1,
    "per_page": 20,
    "total_pages": 1,
    "has_next_page": false,
    "has_previous_page": false
  }
}
```

#### 9. Remove the Second User from Group (Owner Only)

```bash
curl -X DELETE http://localhost:8080/groups/1/members \
  -H "Content-Type: application/json" \
  -d '{"user_id": "550e8400-e29b-41d4-a716-446655440001", "owner_id": "550e8400-e29b-41d4-a716-446655440000"}'
```

Response:
```json
{
  "message": "User removed from the group successfully"
}
```

#### 10. Delete the Group (Owner Only)

```bash
curl -X DELETE http://localhost:8080/groups/1 \
  -H "Content-Type: application/json" \
  -d '{"user_id": "550e8400-e29b-41d4-a716-446655440000"}'
```

Response:
```json
{
  "message": "Group deleted successfully"
}
```

### Using the Test Scripts

The repository includes two test scripts:

1. **api_test.sh**: A bash script for testing all endpoints
   ```bash
   chmod +x api_test.sh
   ./api_test.sh
   ```

2. **api_tester.php**: A PHP script for more detailed testing
   ```bash
   php api_tester.php
   ```

### Using PHPUnit Tests

Run the PHPUnit tests with:

```bash
vendor/bin/phpunit tests/
```

## API Endpoints

### User API
- `POST /users` - Create a new user
- `GET /users/{id}` - Get user by ID

### Group API
- `POST /groups` - Create a new group
- `GET /groups` - Get all groups (with pagination)
- `POST /groups/{id}/join` - Join a group
- `DELETE /groups/{id}/members` - Remove a user from a group (owner only)
- `DELETE /groups/{id}` - Delete a group (owner only)

### Message API
- `POST /messages` - Send a message
- `GET /groups/{id}/messages` - Get all messages in a group (with pagination)

## Security Considerations

- Input data is sanitized to prevent XSS attacks
- Rate limiting is implemented to prevent abuse
- Authentication is required for most endpoints
- Group permissions are enforced for sensitive operations

## Troubleshooting

If you encounter issues:

1. Check the logs in the `logs/` directory
2. Ensure the SQLite database file exists and is writable
3. Verify you have the required PHP extensions enabled (pdo_sqlite)
4. Make sure all directories have the correct permissions
5. If using Windows, update the path separators in the code as needed

### Common Issues and Solutions

**Database Connection Issues**
- Make sure the database file exists in the specified location
- Check file permissions on the database file
- Verify PHP has the pdo_sqlite extension installed

**UUID Handling Issues**
- Ensure UUIDs are consistently treated as strings in your code
- Use explicit type casting when comparing UUIDs

**Permission Issues**
- Set appropriate file permissions for logs directory
- Set appropriate file permissions for storage directory

**API Request Issues**
- Make sure you're using the correct Content-Type header
- Double-check your JSON syntax in request bodies
- Verify that UUIDs are properly formatted
