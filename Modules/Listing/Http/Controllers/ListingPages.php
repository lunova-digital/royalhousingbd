<?php

namespace Modules\Listing\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Listing\Models\Listing;
use Modules\Listing\Models\ListingType;
use Modules\Listing\Services\ListingService;
use Modules\Site\Site;

/** The public listing pages: grids per type (FR-LIST-001/002) and details (FR-LIST-003/004). */
class ListingPages
{
    private const PER_PAGE = 12;

    public function __construct(private ListingService $listings) {}

    public function index(Request $request, ?string $type = null): View
    {
        $all = $type === null;
        if (! $all && ! ListingType::bySlug($type)) {
            abort(404);
        }
        if ($all && ListingType::bySlug((string) $request->query('type'))) {
            $type = $request->query('type');
        }
        $typeName = $type ? ListingType::labelFor($type) : null;

        $features = $this->listings->filterFeatures($type);
        $wanted = array_values(array_intersect((array) $request->query('features', []), array_column($features, 'slug')));
        $filter = [
            'type' => $type,
            'location' => (string) $request->query('location', ''),
            'q' => (string) $request->query('q', ''),
            'features' => $wanted,
            'published' => true,
        ];
        $page = $this->listings->search($filter, self::PER_PAGE, (int) $request->query('page', 1));
        $title = $typeName ? __('site::common.type_listings', ['type' => $typeName]) : __('listing::index.our_projects');

        return view('listing::index', Site::page([
            'title' => $title,
            'description' => __('listing::index.description', ['title' => $title]),
            'nav' => 'projects',
        ], [
            'listings' => $page->items(),
            'total' => $page->total(),
            'pages' => $page->lastPage(),
            'page' => $page->currentPage(),
            'type' => $type,
            'typeName' => $typeName,
            'listingTypes' => ListingType::registry()->values(),
            'locations' => $this->listings->locations($type),
            'features' => $features,
            'filter' => $filter,
            'path' => $all ? '/projects' : "/{$type}",
        ]));
    }

    public function show(string $type, string $slug): View
    {
        $listing = $this->listings->findPublished($type, $slug) ?? abort(404);
        $settings = Site::settings();
        $message = __('listing::show.whatsapp_message', [
            'title' => $listing->title, 'location' => $listing->location, 'ref' => $listing->reference(), 'url' => Site::baseUrl().$listing->url(),
        ]);

        return view('listing::show', Site::page([
            'title' => $listing->title,
            'description' => __('listing::show.description', ['type' => $listing->typeLabel(), 'location' => $listing->location, 'price' => $listing->priceLabel()]),
            'image' => $listing->cover()?->path,
            'nav' => 'projects',
        ], [
            'listing' => $listing,
            'whatsapp' => Site::whatsapp($settings, $message),
            'related' => $this->related($listing),
        ]));
    }

    private function related(Listing $listing): array
    {
        return Listing::query()->with('images')->published()->where('type', $listing->type)
            ->where('id', '!=', $listing->id)->orderByDesc('id')->limit(3)->get()->all();
    }
}
