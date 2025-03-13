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

// Create Container Builder
$containerBuilder = new ContainerBuilder();

// Add container definitions
$containerBuilder->addDefinitions([
    Capsule::class => function() {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../database/chat.sqlite',
            'prefix' => ''
        ]);
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

// User routes
$app->post('/users', [UserController::class, 'create']);
$app->get('/users/{id}', [UserController::class, 'get']);


// Run the app
$app->run();