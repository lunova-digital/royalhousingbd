<?php

use Illuminate\Routing\Router;
use Modules\Listing\Http\Controllers\ListingPages;
use Modules\Listing\Models\Listing;

/** @var Router $router */
$router->get('/projects', [ListingPages::class, 'index']);
$router->redirect('/listings', '/projects');
$router->get('/{type}', [ListingPages::class, 'index'])->whereIn('type', Listing::TYPES);
$router->get('/{type}/{slug}', [ListingPages::class, 'show'])->whereIn('type', Listing::TYPES);
