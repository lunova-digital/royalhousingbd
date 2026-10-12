<?php

use Illuminate\Routing\Router;
use Modules\Content\Http\Controllers\ContentController;
use Modules\Content\Http\Controllers\TextController;

/** @var Router $router */
$router->prefix('admin')->middleware('auth')->group(function (Router $router) {
    $router->get('ticker', [ContentController::class, 'ticker']);
    $router->put('ticker', [ContentController::class, 'saveTicker']);
    $router->get('videos', [ContentController::class, 'videos']);
    $router->put('videos', [ContentController::class, 'saveVideos']);
    $router->get('texts', [TextController::class, 'index']);
    $router->put('texts', [TextController::class, 'update']);
});
