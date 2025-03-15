# Chat Application Backend

A simple chat application backend built with PHP and the Slim framework.

## Features

- Create and manage users
- Create chat groups
- Join existing groups
- Send messages to groups
- Retrieve messages from groups

## Requirements

- PHP 8.0 or higher
- Composer
- SQLite

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/chat-app.git
   cd chat-app
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Create SQLite database:
   ```
   touch database/chat.sqlite
   ```

4. Initialize the database schema:
   ```
   cat database/schema.sql | sqlite3 database/chat.sqlite
   ```

5. Start the development server:
   ```
   php -S localhost:8080 -t public