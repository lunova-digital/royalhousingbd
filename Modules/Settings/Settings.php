<?php

namespace Modules\Settings;

use Illuminate\Support\Facades\DB;
use Modules\Settings\Models\Setting;

/**
 * The admin-editable key/value store behind FR-ADM-005/006: WhatsApp number,
 * contact info, social links, SEO, brand and the homepage hero.
 */
class Settings
{
    /** The closed list of keys the admin may write; anything else is dropped. */
    public const KEYS = [
        // Brand
        'site_name', 'logo',
        // Contact (FR-COMM-001/002)
        'whatsapp_number', 'phone', 'email', 'address', 'map_embed_url', 'about_text',
        // Social links
        'facebook_url', 'youtube_url', 'instagram_url', 'tiktok_url',
        // Basic SEO
        'meta_title', 'meta_description', 'og_image',
        // Homepage hero (FR-HOME-001)
        'hero_title', 'hero_subtitle', 'hero_image', 'hero_video_url', 'hero_cta_text', 'hero_cta_link',
        'hero_badge', 'hero_stats', 'trust_badges', 'working_hours',
        // About page (JSON lists are edited in the admin About tab)
        // Floating chat button (desktop): which options it offers. '0' hides one; the header consult button is unaffected.
        'float_whatsapp', 'float_consult',
        'about_title', 'about_story', 'about_stats', 'team_members', 'partners',
        // Legal pages
        'privacy_text', 'terms_text',
        // Free consultation availability (Modules\Consultation\Availability holds the defaults)
        'consult_enabled', 'consult_days', 'consult_start', 'consult_end', 'consult_slot_minutes',
        'consult_lead_hours', 'consult_horizon_days', 'consult_modes', 'consult_note',
    ];

    public const DEFAULTS = [
        'site_name' => 'Royal Housing BD',
        'hero_title' => 'Find your *perfect* plot, flat or home',
        'hero_subtitle' => 'Browse listings and explore our project masterplans. Contact us on WhatsApp to book a visit.',
        'hero_cta_text' => 'Browse listings',
        'hero_cta_link' => '/projects',
        'hero_badge' => 'Trusted since 2010',
        'hero_stats' => '[{"value":"150+","label":"Projects completed"},{"value":"5000+","label":"Happy families"},{"value":"50+","label":"Locations"}]',
        'trust_badges' => '',
        'float_whatsapp' => '1',
        'float_consult' => '1',
        'working_hours' => 'Saturday - Thursday: 9:00 AM - 6:00 PM',
        'about_title' => 'Building Dreams Into Reality',
        'about_story' => "Royal Housing BD was founded with a vision to bring transparency, quality and innovation to property development in Bangladesh.\n\nWe have delivered residential plots and flats to families across Dhaka, and our interactive masterplan lets you pick your plot with confidence.",
        'about_stats' => '[{"value":"150+","label":"Projects delivered"},{"value":"5000+","label":"Happy families"},{"value":"14+","label":"Years experience"},{"value":"100%","label":"Legal compliance"}]',
        'team_members' => '[]',
        'partners' => '[]',
        'privacy_text' => '',
        'terms_text' => '',
    ];

    /** A JSON list setting, decoded; [] when empty or invalid. */
    public static function list(array $settings, string $key): array
    {
        $decoded = json_decode($settings[$key] ?? '', true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /** @return array<string, string> every setting merged over the defaults */
    public static function all(): array
    {
        return array_merge(self::DEFAULTS, Setting::query()->pluck('value', 'key')->all());
    }

    /** Upsert the allowed keys present in $values; unknown keys are ignored. */
    public static function save(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach (self::KEYS as $key) {
                if (! array_key_exists($key, $values)) {
                    continue;
                }
                Setting::query()->updateOrCreate(['key' => $key], ['value' => (string) ($values[$key] ?? '')]);
            }
        });
    }
}
