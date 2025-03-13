<?php

use App\Controllers\UserController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;
use Slim\Routing\RouteCollectorProxy;

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