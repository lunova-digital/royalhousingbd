<?php

use Illuminate\Routing\Router;
use Modules\Media\Http\Controllers\UploadController;

/** @var Router $router */
$router->prefix('admin')->middleware('auth')->group(function (Router $router) {
    $router->post('uploads', [UploadController::class, 'store']);
});
