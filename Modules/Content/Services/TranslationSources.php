<?php

namespace Modules\Content\Services;

use Illuminate\Support\Facades\DB;
use Modules\Content\Models\TickerItem;
use Modules\Content\Models\Translation;
use Modules\Content\Models\Video;
use Modules\Listing\Models\Feature;
use Modules\Listing\Models\Listing;
use Modules\Listing\Models\ListingType;
use Modules\Listing\Models\Location;
use Modules\Masterplan\Models\Masterplan;
use Modules\Settings\Settings;

/**
 * Everything the admin can translate into Bangla, with what is already translated: the catalogue behind
 * Site → Bangla content and the generated TRANSLATION_GAPS.md. Groups mirror Translations' lookups.
 */
class TranslationSources
{
    /** Settings the public site shows as text (key => admin label). */
    public const SETTINGS = [
        'hero_badge' => 'Hero · eyebrow', 'hero_title' => 'Hero · headline', 'hero_subtitle' => 'Hero · sub-line', 'hero_cta_text' => 'Hero · link label',
        'about_title' => 'About · title', 'about_story' => 'About · story', 'about_text' => 'Footer · short description',
        'address' => 'Address', 'working_hours' => 'Working hours', 'trust_badges' => 'Trusted by',
        'meta_title' => 'SEO · title', 'meta_description' => 'SEO · description', 'consult_note' => 'Consultation · note',
        'privacy_text' => 'Privacy policy', 'terms_text' => 'Terms of service',
    ];

    /** JSON-list settings whose keys hold short English strings (key => [list setting, item field]). */
    private const STRING_LISTS = [['hero_stats', 'label'], ['about_stats', 'label'], ['team_members', 'role'], ['team_members', 'name'], ['partners', 'name']];

    /**
     * @return list<array{id: string, label: string, long: bool, rows: list<array{group: string, key: string, field: string, label: string, en: string, bn: string}>}>
     */
    public function catalogue(): array
    {
        $bn = Translation::query()->where('locale', 'bn')->get()->mapWithKeys(fn ($t) => ["{$t->group}|{$t->key}|{$t->field}" => $t->value])->all();
        $row = fn (string $group, string $key, string $field, string $label, ?string $en) => [
            'group' => $group, 'key' => $key, 'field' => $field, 'label' => $label, 'en' => (string) $en, 'bn' => $bn["{$group}|{$key}|{$field}"] ?? '',
        ];
        $sections = [];
        $section = function (string $id, string $label, array $rows, bool $long = false) use (&$sections) {
            $rows = array_values(array_filter($rows, fn ($r) => trim($r['en']) !== ''));
            if ($rows) {
                $sections[] = compact('id', 'label', 'long', 'rows');
            }
        };

        $s = Settings::all();
        $settingRows = [];
        foreach (self::SETTINGS as $key => $label) {
            $settingRows[] = $row('setting', $key, 'value', $label, $s[$key] ?? Settings::DEFAULTS[$key] ?? '');
        }
        $section('settings', 'Homepage, About & contact text', $settingRows, true);

        $strings = [];
        foreach (self::STRING_LISTS as [$list, $field]) {
            foreach (Settings::list($s, $list) as $item) {
                $en = (string) ($item[$field] ?? '');
                $strings[Translations::stringKey($en)] = $row('string', Translations::stringKey($en), 'value', ucfirst(str_replace('_', ' ', $list)).' · '.$field, $en);
            }
        }
        $section('strings', 'Stats, team & partners', array_values($strings));

        $section('types', 'Listing types', ListingType::query()->ordered()->get()->map(fn ($m) => $row('listing_type', (string) $m->id, 'name', 'Name', $m->getRawOriginal('name')))->all());
        $section('locations', 'Locations', Location::query()->orderBy('name')->get()->map(fn ($m) => $row('location', (string) $m->getRawOriginal('name'), 'value', 'Name', $m->getRawOriginal('name')))->all());
        $section('features', 'Features', Feature::query()->ordered()->get()->map(fn ($m) => $row('feature', (string) $m->id, 'name', 'Name', $m->getRawOriginal('name')))->all());
        $section('listings', 'Listings', Listing::query()->orderByDesc('id')->get()->flatMap(fn ($m) => [
            $row('listing', (string) $m->id, 'title', 'Title · '.$m->reference(), $m->getRawOriginal('title')),
            $row('listing', (string) $m->id, 'description', 'Description · '.$m->reference(), $m->getRawOriginal('description')),
        ])->all(), true);
        $section('masterplans', 'Project maps', Masterplan::query()->orderBy('id')->get()->flatMap(fn ($m) => [
            $row('masterplan', (string) $m->id, 'name', 'Name', $m->getRawOriginal('name')),
            $row('masterplan', (string) $m->id, 'description', 'Description · '.$m->getRawOriginal('name'), $m->getRawOriginal('description')),
        ])->all(), true);
        $section('ticker', 'Announcements', TickerItem::query()->orderBy('sort_order')->get()->map(fn ($m) => $row('ticker', (string) $m->id, 'text', 'Announcement', $m->getRawOriginal('text')))->all());
        $section('videos', 'Videos', Video::query()->orderBy('sort_order')->get()->map(fn ($m) => $row('video', (string) $m->id, 'title', 'Title', $m->getRawOriginal('title')))->all());

        return $sections;
    }

    /** @param  list<array{group: string, key: string, field: string, value: ?string}>  $items */
    public function save(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $i) {
                $where = ['group' => $i['group'], 'key' => $i['key'], 'field' => $i['field'] ?: 'value', 'locale' => 'bn'];
                $value = trim((string) ($i['value'] ?? ''));
                if ($value === '') {
                    Translation::query()->where($where)->delete();
                } else {
                    Translation::query()->updateOrCreate($where, ['value' => $value]);
                }
            }
        });
        Translations::flush();
    }

    /** Markdown for TRANSLATION_GAPS.md: every English value that has no Bangla yet. */
    public function gapsMarkdown(): string
    {
        $out = "# Translation gaps\n\nContent entered in the admin that has no Bangla version yet. The site shows the English text for these until the Bangla is filled in under **Site → Bangla content** in the admin. Regenerate with `php artisan translations:gaps`.\n\n";
        $total = 0;
        foreach ($this->catalogue() as $sec) {
            $missing = array_values(array_filter($sec['rows'], fn ($r) => $r['bn'] === ''));
            if (! $missing) {
                continue;
            }
            $total += count($missing);
            $out .= "## {$sec['label']} (".count($missing).' of '.count($sec['rows']).")\n\n";
            foreach ($missing as $r) {
                $out .= '- '.$r['label'].': '.str_replace("\n", ' ', mb_strimwidth($r['en'], 0, 90, '…'))."\n";
            }
            $out .= "\n";
        }

        return $total ? $out : $out."Nothing missing.\n";
    }
}
