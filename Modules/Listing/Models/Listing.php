<?php

namespace Modules\Listing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Masterplan\Models\Masterplan;

/** Feature 1: a Property / Plot / Flat listing (FR-LIST-*). */
class Listing extends Model
{
    public const TYPES = ['property', 'plot', 'flat'];

    protected $guarded = [];

    protected $attributes = ['facilities' => '[]'];

    protected function casts(): array
    {
        return [
            'call_for_price' => 'boolean',
            'featured' => 'boolean',
            'published' => 'boolean',
            'facilities' => 'array',
            'bedrooms' => 'integer',
            'baths' => 'integer',
            'featured_order' => 'integer',
        ];
    }

    public static function label(string $type): string
    {
        return match ($type) {
            'property' => 'Property',
            'plot' => 'Plot',
            'flat' => 'Flat',
            default => ucfirst($type),
        };
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    /** The masterplan this development belongs to, if any. */
    public function masterplan(): BelongsTo
    {
        return $this->belongsTo(Masterplan::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    /** What the WhatsApp message quotes, e.g. "PLOT-12". */
    public function reference(): string
    {
        return strtoupper($this->type).'-'.$this->id;
    }

    public function typeLabel(): string
    {
        return self::label($this->type);
    }

    public function url(): string
    {
        return "/{$this->type}/{$this->slug}";
    }

    public function cover(): ?ListingImage
    {
        return $this->images->first();
    }

    /** Plots available / total from the linked masterplan, or null. */
    public function availability(): ?array
    {
        $plan = $this->masterplan;
        if (! $plan || $plan->plots->isEmpty()) {
            return null;
        }
        $available = $plan->plots->where('status', 'available')->count();

        return ['available' => $available, 'total' => $plan->plots->count(), 'percent' => (int) round($available * 100 / $plan->plots->count())];
    }

    /** The headline size for a card: land area for property, size otherwise. */
    public function sizeLabel(): string
    {
        return $this->type === 'property' ? ($this->land_area ?: $this->built_area) : $this->size;
    }

    public function priceLabel(): string
    {
        return $this->call_for_price || trim((string) $this->price) === '' ? 'Call for Price' : $this->price;
    }
}
