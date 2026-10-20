<?php

use Illuminate\Routing\Router;
use Modules\Auth\Http\Controllers\AuthController;

/** @var Router $router */
// The admin's door. The public login (Modules\Account) lives at /login.
$router->get('/admin/login', [AuthController::class, 'show'])->name('login');
$router->post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:login');
$router->post('/logout', [AuthController::class, 'logout'])->name('logout');
