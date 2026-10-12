<?php

namespace Modules\Site;

use Modules\Listing\Models\ListingType;
use Modules\Settings\Settings;
use Modules\Site\Support\WhatsApp;

/**
 * What every public page shares: the settings-driven chrome (brand, nav,
 * contact, floating WhatsApp) and SEO meta (NFR-004).
 */
class Site
{
    /** The sitemap from SRS §3, in order. */
    public const NAV = [
        ['key' => 'home', 'label' => 'Home', 'href' => '/', 'icon' => 'home'],
        ['key' => 'about', 'label' => 'About', 'href' => '/about', 'icon' => 'info'],
        ['key' => 'projects', 'label' => 'Projects', 'href' => '/projects', 'icon' => 'building'],
        ['key' => 'map', 'label' => 'Project Map', 'href' => '/map', 'icon' => 'map'],
        ['key' => 'gallery', 'label' => 'Gallery', 'href' => '/gallery', 'icon' => 'image'],
        ['key' => 'contact', 'label' => 'Contact', 'href' => '/contact', 'icon' => 'phone'],
    ];

    /**
     * The listing type sub-navigation (Property · Plot · Flat · …), in the admin's order.
     *
     * @return list<array{key: string, label: string, href: string}>
     */
    public static function types(): array
    {
        return ListingType::registry()->values()
            ->map(fn (ListingType $t) => ['key' => $t->slug, 'label' => $t->name, 'href' => $t->url()])
            ->all();
    }

    private static ?array $settings = null;

    /** Site settings, read once per request. */
    public static function settings(): array
    {
        return self::$settings ??= Settings::all();
    }

    /**
     * Merge the shared chrome with a page's own data.
     *
     * @param  array{title?: string, description?: string, image?: string, nav?: string}  $meta
     */
    public static function page(array $meta = [], array $data = []): array
    {
        $s = self::settings();
        $siteName = $s['site_name'];
        $title = ! empty($meta['title']) ? "{$meta['title']} | {$siteName}" : ($s['meta_title'] ?: $siteName);
        $image = $meta['image'] ?? '' ?: ($s['og_image'] ?? '');
        if ($image !== '' && ! str_starts_with($image, 'http')) {
            $image = self::baseUrl().self::asset($image);
        }

        return $data + [
            'settings' => $s,
            'site_name' => $siteName,
            'title' => $title,
            'description' => $meta['description'] ?? '' ?: ($s['meta_description'] ?? ''),
            'og_image' => $image,
            'canonical' => self::baseUrl().'/'.ltrim(request()->path(), '/'),
            'nav' => self::NAV,
            'types' => self::types(),
            'nav_active' => $meta['nav'] ?? '',
            'whatsapp_float' => self::whatsapp($s, "Hello, I found you on {$siteName} and I'd like to know more."),
        ];
    }

    /** Turn a stored relative path ("uploads/…/x.jpg") into its URL. */
    public static function asset(?string $path): string
    {
        if (! $path) {
            return '';
        }
        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        // Host-relative so uploads resolve on whichever host serves the site.
        return '/storage/'.ltrim($path, '/');
    }

    /** Turn a JSON list setting into rows. */
    public static function list(string $key): array
    {
        return Settings::list(self::settings(), $key);
    }

    public static function whatsapp(array $settings, string $message): string
    {
        return WhatsApp::link($settings['whatsapp_number'] ?? '', $message);
    }

    /** Scheme + host for canonical URLs and share messages. */
    public static function baseUrl(): string
    {
        $configured = rtrim((string) config('app.url'), '/');
        if ($configured !== '' && ! str_starts_with($configured, 'http://localhost')) {
            return $configured;
        }

        return rtrim(request()->getSchemeAndHttpHost(), '/');
    }
}
