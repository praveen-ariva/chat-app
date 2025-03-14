<?php

use App\Controllers\UserController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;
use Slim\Routing\RouteCollectorProxy;

// Define a middleware to set JSON content type for all responses
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response->withHeader('Content-Type', 'application/json');
});

// Users routes
$app->post('/users', [UserController::class, 'create']);
$app->get('/users/{id}', [UserController::class, 'get']);

// Groups routes
$app->post('/groups', [GroupController::class, 'create']);
$app->get('/groups', [GroupController::class, 'getAll']);
$app->post('/groups/{id}/join', [GroupController::class, 'join']);

// Messages routes
$app->post('/messages', [MessageController::class, 'send']);
$app->get('/groups/{id}/messages', [MessageController::class, 'getByGroup']);