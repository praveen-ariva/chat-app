<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Controllers\UserController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/helpers.php';

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create Container Builder
$containerBuilder = new ContainerBuilder();

// Add container definitions
$containerBuilder->addDefinitions([
    Capsule::class => function() {
        $capsule = new Capsule;
        $dbConfig = require __DIR__ . '/../config/database.php';
        debug_log('Database config', $dbConfig);
        $capsule->addConnection($dbConfig);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    },
    UserController::class => function($container) {
        return new UserController($container->get(Capsule::class));
    },
    GroupController::class => function($container) {
        return new GroupController($container->get(Capsule::class));
    },
    MessageController::class => function($container) {
        return new MessageController($container->get(Capsule::class));
    }
]);

// Build PHP-DI Container
$container = $containerBuilder->build();

// Set up Eloquent
$container->get(Capsule::class);

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add middleware
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();

// Define routes
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['message' => 'Chat API is running']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Load all routes
require __DIR__ . '/../src/routes.php';

// Run the app
$app->run();