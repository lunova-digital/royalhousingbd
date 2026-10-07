<?php

use Illuminate\Routing\Router;
use Modules\Dashboard\Http\Controllers\DashboardController;

/** @var Router $router */
$router->prefix('admin')->middleware('auth')->group(function (Router $router) {
    $router->get('dashboard', [DashboardController::class, 'index']);
});
