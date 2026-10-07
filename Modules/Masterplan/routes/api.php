<?php

use Illuminate\Routing\Router;
use Modules\Masterplan\Http\Controllers\MasterplanController;

/** @var Router $router */
$router->prefix('admin')->middleware('auth')->group(function (Router $router) {
    $router->apiResource('masterplans', MasterplanController::class);
    $router->post('masterplans/{masterplan}/image', [MasterplanController::class, 'setImage']);
    $router->put('masterplans/{masterplan}/plots', [MasterplanController::class, 'savePlots']);
});
