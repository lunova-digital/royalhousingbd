<?php

use Illuminate\Routing\Router;
use Modules\Listing\Http\Controllers\ListingPages;

/** @var Router $router */
$router->get('/projects', [ListingPages::class, 'index']);
$router->redirect('/listings', '/projects');

// The 1200x630 link preview of a listing (Open Graph / Twitter), generated on first request and cached.
$router->get('/og/{type}/{slug}.jpg', [ListingPages::class, 'og'])->where(['type' => '[a-z0-9-]+', 'slug' => '[a-z0-9-]+']);

// Listing types are rows, so /{type} cannot be constrained to a fixed list. These are
// fallback routes: they match only after every other route, and the controller
// answers 404 for a slug that is not a type.
$router->get('/{type}', [ListingPages::class, 'index'])->where('type', '[a-z0-9-]+')->fallback();
$router->get('/{type}/{slug}', [ListingPages::class, 'show'])->where('type', '[a-z0-9-]+')->fallback();
