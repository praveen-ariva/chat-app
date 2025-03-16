<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Controllers\UserController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;
use App\Middleware\ContentTypeMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Utils\Logger;
use App\Utils\LoggerFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Set error display based on environment
$appDebug = $_ENV['APP_DEBUG'] ?? false;
ini_set('display_errors', $appDebug ? 1 : 0);
ini_set('display_startup_errors', $appDebug ? 1 : 0);
error_reporting($appDebug ? E_ALL : 0);

// Create Container Builder
$containerBuilder = new ContainerBuilder();

// Create logger directory
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Create storage directory for rate limiting
$storageDir = __DIR__ . '/../storage/rate_limits';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

// Add container definitions
$containerBuilder->addDefinitions([
    Logger::class => function() {
        $logger = new Logger();
        LoggerFactory::setLogger($logger);
        return $logger;
    },
    Capsule::class => function() {
        $capsule = new Capsule;
        
        // Get database config from environment variables
        $dbConfig = [
            'driver' => $_ENV['DB_DRIVER'] ?? 'sqlite',
            'database' => __DIR__ . '/../' . ($_ENV['DB_DATABASE'] ?? 'database/chat.sqlite'),
            'prefix' => $_ENV['DB_PREFIX'] ?? '',
        ];
        
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
    },
    AuthMiddleware::class => function($container) {
        return new AuthMiddleware($container->get(Capsule::class));
    },
    RateLimitMiddleware::class => function() {
        return new RateLimitMiddleware();
    }
]);

// Build PHP-DI Container
$container = $containerBuilder->build();

// Set up Eloquent
$container->get(Capsule::class);

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add global middlewares
$app->addMiddleware(new CorsMiddleware());
$app->addMiddleware(new ContentTypeMiddleware());
$app->addMiddleware($container->get(RateLimitMiddleware::class));
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();

// Define routes
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'message' => 'Chat API is running',
        'version' => '1.0.0'
    ]));
    return $response;
});

// Load all routes
require __DIR__ . '/../src/routes.php';

// Run the app
$app->run();