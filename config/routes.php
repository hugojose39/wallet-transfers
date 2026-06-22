<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;
use App\Interfaces\Http\Controllers\TransferController;
use App\Interfaces\Http\Controllers\UserController;
use App\Interfaces\Http\Controllers\HealthController;
use App\Interfaces\Http\Controllers\DocsController;

Router::addRoute(['GET', 'HEAD'], '/health', [HealthController::class, 'index']);

Router::get('/docs', [DocsController::class, 'ui']);
Router::get('/docs/openapi.yaml', [DocsController::class, 'spec']);

Router::post('/transfer', [TransferController::class, 'store']);
Router::get('/transfer/{id}', [TransferController::class, 'show']);

Router::post('/users', [UserController::class, 'store']);
Router::get('/users/{id}/transfers', [UserController::class, 'transfers']);
Router::get('/users/{id}/wallet', [UserController::class, 'wallet']);
Router::post('/users/{id}/wallet/deposit', [UserController::class, 'deposit']);
