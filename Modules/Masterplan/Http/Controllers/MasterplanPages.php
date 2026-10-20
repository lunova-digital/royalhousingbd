<?php

namespace Modules\Masterplan\Http\Controllers;

use Illuminate\Contracts\View\View;
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
            'title' => __('site::common.project_map_title'),
            'description' => __('masterplan::index.description'),
            'nav' => 'map',
        ], ['masterplans' => $masterplans]));
    }

    public function show(string $slug): View
    {
        $masterplan = Masterplan::query()->with('plots')->published()->where('slug', $slug)->first() ?? abort(404);
        $settings = Site::settings();

        // What the map script consumes: each detailed plot, keyed to its SVG
        // group (FR-MAP-004). The WhatsApp message is composed in the browser
        // from the visitor's selection, so the page ships the number and the
        // message templates rather than a link per plot (FR-MAP-008).
        $plots = $masterplan->plots->map(fn (Plot $plot) => [
            'id' => $plot->id,
            'key' => $plot->svg_key,
            'number' => $plot->number,
            'size' => Site::num((string) $plot->size),
            'price' => Site::num((string) $plot->price),
            'position' => $plot->position,
            'status' => $plot->status,
            'ref' => $plot->reference(),
        ]);

        return view('masterplan::show', Site::page([
            'title' => __('masterplan::show.title', ['name' => $masterplan->name]),
            'description' => __('masterplan::show.description', ['name' => $masterplan->name]),
            'image' => $masterplan->image_path,
            'nav' => 'map',
        ], [
            'masterplan' => $masterplan,
            'counts' => $masterplan->statusCounts(),
            'plan' => [
                'layout' => Site::asset($masterplan->image_path),
                'width' => $masterplan->image_width,
                'height' => $masterplan->image_height,
                'plots' => $plots,
                'whatsapp' => [
                    'number' => preg_replace('/\D+/', '', (string) ($settings['whatsapp_number'] ?? '')),
                    'project' => $masterplan->name,
                    'url' => Site::baseUrl().$masterplan->url(),
                ],
                // The side panel's own words, in the visitor's language. The
                // message templates keep their :placeholders for the script.
                'labels' => [
                    'available' => __('site::common.available'),
                    'booked' => __('site::common.booked'),
                    'sold' => __('site::common.sold'),
                    'plot' => __('site::common.plot'),
                    'callForPrice' => __('site::common.call_for_price'),
                    'loadError' => __('masterplan::show.load_error'),
                    'selected' => __('masterplan::show.n_selected'),
                    'removePlot' => __('masterplan::show.remove_plot'),
                    'messageOne' => __('masterplan::show.whatsapp_message'),
                    'messageMany' => __('masterplan::show.whatsapp_message_many'),
                    'messagePlot' => __('masterplan::show.whatsapp_message_plot'),
                ],
            ],
        ]));
    }
}
