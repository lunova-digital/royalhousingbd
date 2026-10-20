<?php

namespace Modules\Listing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Modules\Content\Models\Concerns\HasTranslations;

/**
 * A kind of listing (Property, Plot, Flat, Resort…), managed in the admin. The
 * slug is the public URL segment (/plot, /plot/{listing}) and the value of
 * listings.type, so it is fixed once created.
 */
class ListingType extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public static array $translatable = ['name'];

    public static function translationGroup(): string
    {
        return 'listing_type';
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    private static ?Collection $registry = null;

    protected static function booted(): void
    {
        static::saved(fn () => self::$registry = null);
        static::deleted(fn () => self::$registry = null);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'type', 'slug');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Every type keyed by slug, in order; read once per request. */
    public static function registry(): Collection
    {
        return self::$registry ??= self::query()->ordered()->get()->keyBy('slug');
    }

    public static function bySlug(string $slug): ?self
    {
        return self::registry()->get($slug);
    }

    public static function labelFor(string $slug): string
    {
        return self::bySlug($slug)?->name ?? ucfirst(str_replace('-', ' ', $slug));
    }

    public function url(): string
    {
        return "/{$this->slug}";
    }

    /**
     * Slugs a type may not use: the first segment of every static route (about,
     * map, admin, login…) plus the directories served from public/.
     */
    public static function reservedSlugs(): array
    {
        $segments = ['storage', 'build', 'projects', 'listings'];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $first = explode('/', trim($route->uri(), '/'))[0];
            if ($first !== '' && ! str_contains($first, '{')) {
                $segments[] = $first;
            }
        }

        return array_values(array_unique($segments));
    }
}
