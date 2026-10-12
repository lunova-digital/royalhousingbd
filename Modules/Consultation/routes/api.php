<?php

use Illuminate\Routing\Router;
use Modules\Consultation\Http\Controllers\ConsultationController;

/** @var Router $router */
$router->prefix('admin')->middleware('auth')->group(function (Router $router) {
    $router->get('consultations/availability', [ConsultationController::class, 'availability']);
    $router->put('consultations/availability', [ConsultationController::class, 'saveAvailability']);
    $router->get('consultations', [ConsultationController::class, 'index']);
    $router->put('consultations/{consultation}', [ConsultationController::class, 'update']);
    $router->delete('consultations/{consultation}', [ConsultationController::class, 'destroy']);
});
