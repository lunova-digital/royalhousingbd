<?php

namespace Modules\Listing\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Listing\Models\Listing;
use Modules\Listing\Models\ListingType;
use Modules\Listing\Services\ListingService;
use Modules\Listing\Support\OgImage;
use Modules\Listing\Support\Price;
use Modules\Site\Site;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        [$min, $max] = [Price::bound($request->query('budget_min')), Price::bound($request->query('budget_max'))];
        if ($min > 0 && $max > 0 && $min > $max) {
            [$min, $max] = [$max, $min];
        }
        // `locations[]` (several) and the older single `location` both work.
        $locations = array_values(array_unique(array_filter(array_map('strval', array_merge((array) $request->query('locations', []), (array) $request->query('location', []))), fn ($l) => trim($l) !== '')));
        $sort = in_array($request->query('sort'), ['price_asc', 'price_desc'], true) ? $request->query('sort') : 'newest';
        $filter = [
            'type' => $type,
            'location' => $locations,
            'sort' => $sort,
            'q' => (string) $request->query('q', ''),
            'features' => $wanted,
            'budget_min' => $min,
            'budget_max' => $max,
            'published' => true,
        ];
        $page = $this->listings->search($filter, self::PER_PAGE, (int) $request->query('page', 1));
        $title = $typeName ? __('site::common.type_listings', ['type' => $typeName]) : __('listing::index.our_projects');

        // The filter bar updates the results in place: it asks for just the results block.
        $view = $request->boolean('fragment') ? 'listing::partials.results' : 'listing::index';

        return view($view, Site::page([
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
            'budgetSteps' => Price::STEPS,
            'path' => $all ? '/projects' : "/{$type}",
            'perPage' => self::PER_PAGE,
        ]));
    }

    /** The link-preview image of a listing; falls back to the site's own image if it cannot be generated. */
    public function og(string $type, string $slug): BinaryFileResponse|RedirectResponse
    {
        $listing = $this->listings->findPublished($type, $slug) ?? abort(404);
        $path = OgImage::path($listing);
        if ($path === null) {
            return redirect(Site::asset(Site::settings()['og_image'] ?? '') ?: '/apple-touch-icon.png');
        }

        return response()->file($path, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'public, max-age=86400']);
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
            // Link previews (Facebook, WhatsApp, X…): title + price, location · size · type, and a 1200x630 image (photo or branded card)
            'og_title' => $listing->title.' — '.($listing->call_for_price || trim((string) $listing->price) === '' ? __('listing::card.price_on_request') : $listing->price),
            'og_description' => implode(' · ', array_filter([$listing->location, $listing->sizeLabel(), $listing->typeLabel(), __('site::common.ref', ['ref' => $listing->reference()])])),
            'image' => OgImage::url($listing),
            'image_size' => [OgImage::WIDTH, OgImage::HEIGHT],
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
