<?php

use Illuminate\Routing\Router;
use Modules\Service\Http\Controllers\ServicePages;

/** @var Router $router */
$router->get('/services/{slug}', [ServicePages::class, 'show'])->where('slug', '[a-z0-9-]+');
