<?php

namespace Modules\Masterplan\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /** The map page, or (?json=1) just the plan, so the project switcher can swap maps without a reload. */
    public function show(Request $request, string $slug): View|JsonResponse
    {
        $masterplan = Masterplan::query()->with('plots')->published()->where('slug', $slug)->first() ?? abort(404);
        $plan = $this->plan($masterplan);

        if ($request->boolean('json')) {
            return response()->json($plan);
        }

        return view('masterplan::show', Site::page([
            'title' => __('masterplan::show.title', ['name' => $masterplan->name]),
            'description' => __('masterplan::show.description', ['name' => $masterplan->name]),
            'image' => $masterplan->image_path,
            'nav' => 'map',
        ], [
            'masterplan' => $masterplan,
            'counts' => $masterplan->statusCounts(),
            'plan' => $plan,
            'projects' => Masterplan::query()->published()->orderBy('id')->get(['id', 'name', 'slug'])->map(fn (Masterplan $m) => ['name' => $m->name, 'slug' => $m->slug, 'url' => $m->url()])->all(),
            // The words the map script needs, in the visitor's language. Message templates keep their :placeholders.
            'mapLabels' => $this->labels(),
        ]));
    }

    /**
     * What the map script consumes: the layout, and each detailed plot keyed to its SVG group (FR-MAP-004).
     * Numbers and sizes arrive ready to display (বাং digits and units); `raw` / `sizeRaw` are the stored values.
     * The WhatsApp message is composed in the browser from the visitor's selection (FR-MAP-008).
     */
    private function plan(Masterplan $masterplan): array
    {
        $settings = Site::settings();

        return [
            'slug' => $masterplan->slug,
            'name' => $masterplan->name,
            'description' => (string) $masterplan->description,
            'url' => $masterplan->url(),
            'layout' => Site::asset($masterplan->image_path),
            'width' => $masterplan->image_width,
            'height' => $masterplan->image_height,
            'counts' => $masterplan->statusCounts(),
            'plots' => $masterplan->plots->map(fn (Plot $plot) => [
                'id' => $plot->id,
                'key' => $plot->svg_key,
                'number' => Site::digits((string) $plot->number),
                'raw' => (string) $plot->number,
                'size' => Site::num((string) $plot->size),
                'sizeRaw' => (string) $plot->size,
                'price' => Site::num((string) $plot->price),
                'position' => $plot->position,
                'status' => $plot->status,
                'ref' => $plot->reference(),
            ])->all(),
            'whatsapp' => [
                'number' => preg_replace('/\D+/', '', (string) ($settings['whatsapp_number'] ?? '')),
                'project' => $masterplan->name,
                'url' => Site::baseUrl().$masterplan->url(),
            ],
        ];
    }

    private function labels(): array
    {
        $t = fn (string $key) => __('masterplan::show.'.$key);

        return [
            'available' => __('site::common.available'), 'booked' => __('site::common.booked'), 'sold' => __('site::common.sold'),
            'plot' => __('site::common.plot'), 'callForPrice' => __('listing::card.price_on_request'),
            'loadError' => $t('load_error'), 'selected' => $t('n_selected'), 'selectedTotal' => $t('n_selected_total'),
            'removePlot' => $t('remove_plot'), 'plotLabel' => $t('plot_label'), 'plotAria' => $t('plot_aria'),
            'findNone' => $t('find_none'), 'askSimilar' => $t('ask_similar'), 'book' => __('site::common.book_now'),
            'msgOne' => $t('msg_one'), 'msgMany' => $t('msg_many'), 'msgSimilar' => $t('msg_similar'), 'msgSimilarMany' => $t('msg_similar_many'),
            'msgEach' => $t('msg_each'), 'msgItem' => $t('msg_item'), 'msgItemStatus' => $t('msg_item_status'),
            'msgItemNoSize' => $t('msg_item_nosize'), 'msgItemStatusNoSize' => $t('msg_item_status_nosize'),
            'msgMap' => $t('msg_map'), 'notePlots' => $t('note_plots'),
            'fullScreen' => $t('full_screen'), 'exitFullScreen' => $t('exit_full_screen'),
        ];
    }
}
