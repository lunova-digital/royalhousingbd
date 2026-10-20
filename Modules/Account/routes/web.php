<?php

use Illuminate\Routing\Router;
use Modules\Account\Http\Controllers\AuthController;
use Modules\Account\Http\Controllers\PasswordController;
use Modules\Account\Http\Controllers\SocialController;

/** @var Router $router */
$router->get('/login', [AuthController::class, 'showLogin'])->name('member.login');
$router->post('/login', [AuthController::class, 'login'])->middleware('throttle:member-login');
$router->get('/register', [AuthController::class, 'showRegister'])->name('member.register');
$router->post('/register', [AuthController::class, 'register'])->middleware('throttle:member-register');

$router->get('/forgot-password', [PasswordController::class, 'showForgot'])->name('member.forgot');
$router->post('/forgot-password', [PasswordController::class, 'sendReset'])->middleware('throttle:member-reset');
$router->get('/forgot-password/code', [PasswordController::class, 'showCode']);
$router->post('/forgot-password/code', [PasswordController::class, 'resetWithCode'])->middleware('throttle:member-reset');
$router->get('/reset-password/{token}', [PasswordController::class, 'showReset']);
$router->post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:member-reset');

$router->get('/auth/google', [SocialController::class, 'redirect']);
$router->get('/auth/google/callback', [SocialController::class, 'callback']);
