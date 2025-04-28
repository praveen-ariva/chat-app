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
- **API Documentation**: Interactive Swagger UI for API testing and documentation

## Technical Stack

- **PHP 8.0+**: Modern PHP language features
- **Slim Framework**: Lightweight PHP framework for routing and middleware
- **Eloquent ORM**: Database abstraction and model relationships
- **SQLite**: Simple, fast, and file-based database
- **PHPUnit**: Comprehensive testing
- **Swagger UI**: Interactive API documentation

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

## API Documentation

The application includes Swagger UI for interactive API documentation and testing.

### Accessing Swagger UI

After starting the application, access the Swagger UI at:
```
http://localhost:8080/api-docs
```

This interactive documentation allows you to:
- Browse all available API endpoints
- See required parameters and expected responses
- Test API endpoints directly from your browser
- Understand the API structure and relationships

### API Endpoints

#### User API
- `POST /users` - Create a new user
- `GET /users/{id}` - Get user by ID

#### Group API
- `POST /groups` - Create a new group
- `GET /groups` - Get all groups (with pagination)
- `POST /groups/{id}/join` - Join a group
- `DELETE /groups/{id}/members` - Remove a user from a group (owner only)
- `DELETE /groups/{id}` - Delete a group (owner only)

#### Message API
- `POST /messages` - Send a message
- `GET /groups/{id}/messages` - Get all messages in a group (with pagination)

## Testing the API

### Using Swagger UI

The easiest way to test the API is through the Swagger UI interface at `/api-docs`. You can execute requests and see responses directly in your browser.

### Using curl

You can also test the API using curl commands. Here are some examples:

#### 1. Create a User

```bash
curl -X POST http://localhost:8080/users \
  -H "Content-Type: application/json" \
  -d '{"username": "testuser1"}'
```

#### 2. Create a Group

```bash
curl -X POST http://localhost:8080/groups \
  -H "Content-Type: application/json" \
  -d '{"name": "testgroup", "user_id": "550e8400-e29b-41d4-a716-446655440000"}'
```

#### 3. Send a Message

```bash
curl -X POST http://localhost:8080/messages \
  -H "Content-Type: application/json" \
  -d '{"user_id": "550e8400-e29b-41d4-a716-446655440000", "group_id": 1, "content": "Hello, this is a test message!"}'
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
