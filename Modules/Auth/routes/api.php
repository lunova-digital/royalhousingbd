<?php

use Illuminate\Routing\Router;
use Modules\Auth\Http\Controllers\AuthController;

/** @var Router $router */
$router->put('auth/password', [AuthController::class, 'password'])->middleware('auth');
