<?php

namespace Modules\Home\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Modules\Content\Models\TickerItem;
use Modules\Content\Models\Video;
use Modules\Listing\Models\Listing;
use Modules\Listing\Models\ListingType;
use Modules\Listing\Services\ListingService;
use Modules\Masterplan\Models\Masterplan;
use Modules\Site\Site;

/** The homepage (FR-HOME-*), About/Contact (FR-COMM-002), sitemap.xml and robots.txt. */
class HomeController
{
    public function __construct(private ListingService $listings) {}

    public function index(): View
    {
        return view('home::index', Site::page(['nav' => 'home'], [
            'featured' => $this->listings->featured(),
            'latest' => Listing::query()->with('images')->published()->orderByDesc('id')->limit(8)->get()->all(),
            'ticker' => TickerItem::query()->where('active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'videos' => Video::query()->orderBy('sort_order')->orderBy('id')->get(),
            'maps' => Masterplan::query()->with('plots')->published()->orderBy('id')->get(),
        ]));
    }

    public function about(): View
    {
        $settings = Site::settings();

        return view('home::about', Site::page([
            'title' => 'About Us',
            'description' => $settings['about_title'],
            'nav' => 'about',
        ], [
            'stats' => Site::list('about_stats'),
            'team' => Site::list('team_members'),
            'partners' => Site::list('partners'),
        ]));
    }

    /** Every photo on the site, grouped by listing type and masterplans, for the gallery grid. */
    public function gallery(): View
    {
        $photos = [];
        foreach (Listing::query()->with('images')->published()->orderByDesc('id')->get() as $listing) {
            foreach ($listing->images as $image) {
                $photos[] = ['src' => $image->path, 'thumb' => $image->thumb_path ?: $image->path, 'category' => $listing->type, 'caption' => $listing->title, 'location' => $listing->location, 'href' => $listing->url()];
            }
        }
        foreach (Masterplan::query()->published()->orderByDesc('id')->get() as $masterplan) {
            if ($masterplan->image_path) {
                $photos[] = ['src' => $masterplan->image_path, 'thumb' => $masterplan->image_path, 'category' => 'masterplan', 'caption' => $masterplan->name, 'location' => 'Masterplan', 'href' => $masterplan->url()];
            }
        }

        return view('home::gallery', Site::page([
            'title' => 'Gallery',
            'description' => 'Photos of our properties, plots, flats and project masterplans.',
            'nav' => 'gallery',
        ], ['photos' => $photos, 'listingTypes' => ListingType::registry()->values()]));
    }

    public function privacy(): View
    {
        return view('home::legal', Site::page(['title' => 'Privacy Policy'], ['heading' => 'Privacy Policy', 'body' => Site::settings()['privacy_text']]));
    }

    public function terms(): View
    {
        return view('home::legal', Site::page(['title' => 'Terms of Service'], ['heading' => 'Terms of Service', 'body' => Site::settings()['terms_text']]));
    }

    public function contact(): View
    {
        $settings = Site::settings();

        return view('home::contact', Site::page([
            'title' => 'Contact Us',
            'description' => 'Get in touch with '.$settings['site_name'].' by phone, email or WhatsApp.',
            'nav' => 'contact',
        ], [
            'whatsapp' => Site::whatsapp($settings, "Hello, I'd like to get in touch about your listings."),
        ]));
    }

    public function sitemap(): Response
    {
        $base = Site::baseUrl();
        $urls = collect(Site::NAV)->map(fn ($item) => [$item['href'], now()]);
        foreach (ListingType::registry() as $type) {
            $urls->push([$type->url(), now()]);
        }
        foreach (Listing::query()->published()->get(['type', 'slug', 'updated_at']) as $listing) {
            $urls->push([$listing->url(), $listing->updated_at]);
        }
        foreach (Masterplan::query()->published()->get(['slug', 'updated_at']) as $masterplan) {
            $urls->push([$masterplan->url(), $masterplan->updated_at]);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as [$path, $modified]) {
            $xml .= sprintf("  <url><loc>%s</loc><lastmod>%s</lastmod></url>\n", e($base.$path), $modified->toDateString());
        }
        $xml .= "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function robots(): Response
    {
        return response("User-agent: *\nDisallow: /admin\nDisallow: /api\nSitemap: ".Site::baseUrl()."/sitemap.xml\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
