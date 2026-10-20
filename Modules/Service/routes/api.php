<?php

use Illuminate\Routing\Router;
use Modules\Service\Http\Controllers\ServiceController;

/** @var Router $router */
$router->prefix('admin')->middleware('auth')->group(function (Router $router) {
    $router->get('services', [ServiceController::class, 'index']);
    $router->put('services', [ServiceController::class, 'save']);
});
