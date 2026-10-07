<?php

use Illuminate\Routing\Router;
use Modules\Home\Http\Controllers\HomeController;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/gallery', [HomeController::class, 'gallery']);
$router->get('/contact', [HomeController::class, 'contact']);
$router->get('/privacy', [HomeController::class, 'privacy']);
$router->get('/terms', [HomeController::class, 'terms']);
$router->get('/sitemap.xml', [HomeController::class, 'sitemap']);
$router->get('/robots.txt', [HomeController::class, 'robots']);
