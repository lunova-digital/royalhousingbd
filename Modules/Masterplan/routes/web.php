<?php

use Illuminate\Routing\Router;
use Modules\Masterplan\Http\Controllers\MasterplanPages;

/** @var Router $router */
$router->get('/map', [MasterplanPages::class, 'index']);
$router->get('/map/{slug}', [MasterplanPages::class, 'show']);
