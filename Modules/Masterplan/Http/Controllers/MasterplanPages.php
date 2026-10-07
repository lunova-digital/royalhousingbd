<?php

namespace Modules\Masterplan\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Modules\Masterplan\Models\Masterplan;
use Modules\Masterplan\Models\Plot;
use Modules\Site\Site;

/** The public map pages (FR-MAP-002..008). */
class MasterplanPages
{
    public function index(): View
    {
        $masterplans = Masterplan::query()->with('plots')->published()->orderBy('id')->get();

        return view('masterplan::index', Site::page([
            'title' => 'Project Map',
            'description' => 'Choose a project to explore its interactive masterplan with live plot availability.',
            'nav' => 'map',
        ], ['masterplans' => $masterplans]));
    }

    public function show(string $slug): View
    {
        $masterplan = Masterplan::query()->with('plots')->published()->where('slug', $slug)->first() ?? abort(404);
        $settings = Site::settings();

        // What the map script consumes: each detailed plot, keyed to its SVG
        // group, plus a ready-made WhatsApp link (FR-MAP-004, FR-MAP-008).
        $plots = $masterplan->plots->map(function (Plot $plot) use ($masterplan, $settings) {
            $message = sprintf('Hello, I\'m interested in Plot %s on %s (Ref: %s). %s%s',
                $plot->number, $masterplan->name, $plot->reference(), Site::baseUrl(), $masterplan->url());

            return [
                'id' => $plot->id,
                'key' => $plot->svg_key,
                'number' => $plot->number,
                'size' => $plot->size,
                'price' => $plot->price,
                'position' => $plot->position,
                'status' => $plot->status,
                'ref' => $plot->reference(),
                'whatsapp' => Site::whatsapp($settings, $message),
            ];
        });

        return view('masterplan::show', Site::page([
            'title' => $masterplan->name.' — Project Map',
            'description' => "Tap a plot on the {$masterplan->name} masterplan to see its size, price and availability.",
            'image' => $masterplan->image_path,
            'nav' => 'map',
        ], [
            'masterplan' => $masterplan,
            'counts' => $masterplan->statusCounts(),
            'plan' => [
                'layout' => $masterplan->image_path ? Storage::disk('public')->url($masterplan->image_path) : '',
                'width' => $masterplan->image_width,
                'height' => $masterplan->image_height,
                'plots' => $plots,
            ],
        ]));
    }
}
