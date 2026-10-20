<?php

namespace Modules\Service\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Service\Models\Service;
use Modules\Site\Site;

/** The public page of one of the other services. Inactive services are not found. */
class ServicePages
{
    public function show(string $slug): View
    {
        $service = Service::query()->active()->where('slug', $slug)->first() ?? abort(404);
        $settings = Site::settings();
        $title = $service->text('title');
        $image = $service->image ?: ($service->gallery[0] ?? '');

        return view('service::show', Site::page([
            'title' => $title,
            'description' => $service->text('summary'),
            'image' => $image,
            'nav' => '',
        ], [
            'service' => $service,
            'whatsapp' => Site::whatsapp($settings, __('service::service.whatsapp_message', ['service' => $title])),
            'others' => Service::query()->active()->ordered()->where('id', '!=', $service->id)->get(),
        ]));
    }
}
