<?php

namespace Modules\Content\Translation;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Modules\Content\Models\TextOverride;
use Throwable;

/**
 * Wraps Laravel's file loader so every public string can be rewritten from the admin
 * (Site → Site text): rows of `text_overrides` replace the matching lines of a lang file
 * as it loads. Blade keeps calling `__('home::index.faq')`; nothing else changes.
 */
class TextOverrideLoader implements Loader
{
    /** @var array<string, array<string, string>> overrides per locale, read once per process */
    private array $overrides = [];

    public function __construct(private Loader $inner) {}

    public function load($locale, $group, $namespace = null)
    {
        $lines = $this->inner->load($locale, $group, $namespace);
        if ($group === '*') {
            return $lines;
        }
        $prefix = ($namespace === null || $namespace === '*') ? "{$group}." : "{$namespace}::{$group}.";
        foreach ($this->overrides($locale) as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                Arr::set($lines, substr($key, strlen($prefix)), $value);
            }
        }

        return $lines;
    }

    /** Drop what was read so the next load sees a fresh save. */
    public function forget(): void
    {
        $this->overrides = [];
    }

    public function inner(): Loader
    {
        return $this->inner;
    }

    public function addNamespace($namespace, $hint)
    {
        $this->inner->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path)
    {
        $this->inner->addJsonPath($path);
    }

    public function namespaces()
    {
        return $this->inner->namespaces();
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->inner->{$method}(...$arguments);
    }

    /** @return array<string, string> */
    private function overrides(string $locale): array
    {
        if (! array_key_exists($locale, $this->overrides)) {
            try {
                // Before the table exists (first migrate) there is nothing to apply; do not remember that.
                if (! Schema::hasTable('text_overrides')) {
                    return [];
                }
                $this->overrides[$locale] = TextOverride::query()->where('locale', $locale)->pluck('value', 'key')->all();
            } catch (Throwable) {
                return [];
            }
        }

        return $this->overrides[$locale];
    }
}
