<?php

use Illuminate\Routing\Router;
use Modules\Auth\Http\Controllers\AuthController;

/** @var Router $router */
$router->get('/login', [AuthController::class, 'show'])->name('login');
$router->post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
$router->post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
