<?php

use Illuminate\Routing\Router;
use Modules\Settings\Http\Controllers\SettingsController;

/** @var Router $router */
$router->prefix('admin')->middleware('auth')->group(function (Router $router) {
    $router->get('settings', [SettingsController::class, 'index']);
    $router->put('settings', [SettingsController::class, 'update']);
});
