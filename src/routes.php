<?php

use App\Controllers\UserController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;
use App\Middleware\AuthMiddleware;
use Slim\Routing\RouteCollectorProxy;

// Get AuthMiddleware from container
$authMiddleware = $app->getContainer()->get(AuthMiddleware::class);

// Users routes
$app->post('/users', [UserController::class, 'create']);
$app->get('/users/{id}', [UserController::class, 'get']);

// Groups routes
$app->group('/groups', function (RouteCollectorProxy $group) {
    // Create and list groups
    $group->post('', [GroupController::class, 'create']);
    $group->get('', [GroupController::class, 'getAll']);
    
    // Group-specific routes
    $group->post('/{id}/join', [GroupController::class, 'join']);
    $group->delete('/{id}/members', [GroupController::class, 'removeUser']);
    $group->delete('/{id}', [GroupController::class, 'delete']);
    
    // Group messages
    $group->get('/{id}/messages', [MessageController::class, 'getByGroup']);
})->add($authMiddleware);

// Messages routes
$app->post('/messages', [MessageController::class, 'send'])->add($authMiddleware);