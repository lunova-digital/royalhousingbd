<?php

use Illuminate\Routing\Router;
use Modules\Consultation\Http\Controllers\ConsultationPages;

/** @var Router $router */
$router->get('/consultation', [ConsultationPages::class, 'index'])->name('consultation.index');
$router->get('/consultation/availability', [ConsultationPages::class, 'availability'])->middleware('throttle:60,1');
$router->post('/consultation', [ConsultationPages::class, 'store'])->middleware('throttle:12,1');
$router->get('/consultation/{reference}', [ConsultationPages::class, 'show'])->where('reference', 'RC-[A-Z0-9]{6}');
$router->get('/consultation/{reference}/calendar.ics', [ConsultationPages::class, 'ics'])->where('reference', 'RC-[A-Z0-9]{6}');
