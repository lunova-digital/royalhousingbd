<?php

namespace Modules\Content\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Content\Models\TextOverride;
use Modules\Content\Translation\TextOverrideLoader;
use Modules\Site\Site;

/**
 * The catalogue behind Site → Site text: every string of the public site (the modules'
 * lang/en files, with their Bangla) and what the admin wrote over it.
 */
class SiteTexts
{
    /** The lang files as the admin sees them, in this order; others follow with a generated label. */
    public const GROUPS = [
        'site::layout' => 'Header & footer',
        'site::common' => 'Shared labels',
        'home::index' => 'Homepage',
        'home::about' => 'About page',
        'home::contact' => 'Contact page',
        'home::gallery' => 'Gallery',
        'home::legal' => 'Privacy & Terms',
        'listing::index' => 'Projects grid',
        'listing::card' => 'Listing cards',
        'listing::show' => 'Listing page',
        'masterplan::index' => 'Project map list',
        'masterplan::show' => 'Project map',
        'consultation::page' => 'Consultation page',
        'consultation::widget' => 'Consultation widget',
        'errors' => 'Error pages',
    ];

    /**
     * Every group with its strings: the English and Bangla defaults and the admin's text for each.
     *
     * @return list<array{id: string, label: string, items: list<array{key: string, en: string, bn: string, en_override: string, bn_override: string}>}>
     */
    public function catalogue(): array
    {
        $overrides = $this->overrides();
        $groups = [];
        foreach ($this->files() as $id => $dir) {
            $defaults = [];
            foreach (array_keys(Site::LOCALES) as $locale) {
                $file = "{$dir}/{$locale}/".Str::afterLast($id, '::').'.php';
                $defaults[$locale] = is_file($file) ? Arr::dot((array) require $file) : [];
            }
            $items = [];
            foreach ($defaults['en'] as $item => $en) {
                $key = "{$id}.{$item}";
                $items[] = [
                    'key' => $key,
                    'en' => (string) $en,
                    'bn' => (string) ($defaults['bn'][$item] ?? ''),
                    'en_override' => $overrides['en'][$key] ?? '',
                    'bn_override' => $overrides['bn'][$key] ?? '',
                ];
            }
            if ($items) {
                $groups[$id] = ['id' => $id, 'label' => self::GROUPS[$id] ?? Str::headline(str_replace('::', ' · ', $id)), 'items' => $items];
            }
        }
        $order = array_flip(array_keys(self::GROUPS));
        uksort($groups, fn ($a, $b) => ($order[$a] ?? PHP_INT_MAX) <=> ($order[$b] ?? PHP_INT_MAX) ?: strcmp($a, $b));

        return array_values($groups);
    }

    /**
     * Store the admin's texts: a non-empty value replaces the default in that language, an empty one restores it.
     * Keys outside the catalogue are ignored.
     *
     * @param  array<string, array{en?: ?string, bn?: ?string}>  $texts
     */
    public function save(array $texts): void
    {
        $known = array_fill_keys(array_merge(...array_map(fn ($g) => array_column($g['items'], 'key'), $this->catalogue())), true);
        DB::transaction(function () use ($texts, $known) {
            foreach ($texts as $key => $values) {
                if (! isset($known[$key]) || ! is_array($values)) {
                    continue;
                }
                foreach (array_keys(Site::LOCALES) as $locale) {
                    if (! array_key_exists($locale, $values)) {
                        continue;
                    }
                    $value = trim((string) $values[$locale]);
                    if ($value === '') {
                        TextOverride::query()->where('locale', $locale)->where('key', $key)->delete();
                    } else {
                        TextOverride::query()->updateOrCreate(['locale' => $locale, 'key' => $key], ['value' => $value]);
                    }
                }
            }
        });
        $this->flush();
    }

    /** Make the translator read the overrides again. */
    public function flush(): void
    {
        $translator = app('translator');
        if (($loader = $translator->getLoader()) instanceof TextOverrideLoader) {
            $loader->forget();
        }
        $translator->setLoaded([]);
    }

    /** @return array<string, array<string, string>> locale => key => text */
    private function overrides(): array
    {
        $out = [];
        foreach (TextOverride::query()->get(['locale', 'key', 'value']) as $row) {
            $out[$row->locale][$row->key] = $row->value;
        }

        return $out;
    }

    /**
     * The lang directories that hold public copy: each module's, namespaced, and the app's for the error pages.
     *
     * @return array<string, string> "home::index" => the lang dir
     */
    private function files(): array
    {
        // The modules register their namespaces when the translator resolves, so ask it, not the loader binding.
        $dirs = ['' => lang_path()];
        foreach (app('translator')->getLoader()->namespaces() as $namespace => $dir) {
            $dirs["{$namespace}::"] = $dir;
        }
        $out = [];
        foreach ($dirs as $prefix => $dir) {
            foreach (glob("{$dir}/en/*.php") ?: [] as $file) {
                $out[$prefix.basename($file, '.php')] = $dir;
            }
        }

        return $out;
    }
}
