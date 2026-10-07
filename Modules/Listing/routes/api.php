<?php

use Illuminate\Routing\Router;
use Modules\Listing\Http\Controllers\ListingController;

/** @var Router $router */
$router->prefix('admin')->middleware('auth')->group(function (Router $router) {
    $router->get('listings/featured', [ListingController::class, 'featured']);
    $router->put('listings/featured', [ListingController::class, 'setFeatured']);
    $router->apiResource('listings', ListingController::class);
    $router->post('listings/{listing}/images', [ListingController::class, 'addImages']);
    $router->put('listings/{listing}/images/order', [ListingController::class, 'reorderImages']);
    $router->delete('listings/{listing}/images/{image}', [ListingController::class, 'deleteImage']);
});
