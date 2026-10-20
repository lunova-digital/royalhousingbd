<?php

namespace Modules\Listing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Account\Models\SavedListing;
use Modules\Content\Models\Concerns\HasTranslations;
use Modules\Masterplan\Models\Masterplan;

/** Feature 1: a listing of any ListingType (FR-LIST-*), with its features (amenities). */
class Listing extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public static array $translatable = ['title', 'description', 'location'];

    /** @var list<string> */
    public static array $localizedNumbers = ['price', 'size', 'land_area', 'built_area', 'floor'];

    public static function translationGroup(): string
    {
        return 'listing';
    }

    /** A listing's location is the location's English name, translated through the locations group. */
    public function translationGroupFor(string $field): string
    {
        return $field === 'location' ? 'location' : 'listing';
    }

    public function translationField(string $field): string
    {
        return $field === 'location' ? 'value' : $field;
    }

    public function translationKey(string $field): string
    {
        return $field === 'location' ? (string) $this->getRawOriginal('location') : (string) $this->getKey();
    }

    protected $guarded = [];

    protected $appends = ['type_label'];

    protected function casts(): array
    {
        return [
            'call_for_price' => 'boolean',
            'price_amount' => 'integer',
            'featured' => 'boolean',
            'published' => 'boolean',
            'bedrooms' => 'integer',
            'baths' => 'integer',
            'featured_order' => 'integer',
            'brochure_size' => 'integer',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    /** The members who saved this listing (a useful lead signal; the admin list shows the count). */
    public function savedBy(): HasMany
    {
        return $this->hasMany(SavedListing::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class)->orderBy('name');
    }

    /** The masterplan this development belongs to, if any. */
    public function masterplan(): BelongsTo
    {
        return $this->belongsTo(Masterplan::class);
    }

    public function listingType(): BelongsTo
    {
        return $this->belongsTo(ListingType::class, 'type', 'slug');
    }

    /** The admin-managed Location the `location` name is copied from. */
    public function locationRecord(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
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
        return ListingType::labelFor($this->type);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->typeLabel();
    }

    public function url(): string
    {
        return "/{$this->type}/{$this->slug}";
    }

    public function hasBrochure(): bool
    {
        return (string) $this->brochure_path !== '';
    }

    /** The file name the brochure downloads as, e.g. "corner-plot-block-c-brochure.pdf". */
    public function brochureFilename(): string
    {
        return "{$this->slug}-brochure.pdf";
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

    /** The headline size for a card: land area for property, size otherwise, falling back to whatever is filled in. */
    public function sizeLabel(): string
    {
        return $this->type === 'property'
            ? ($this->land_area ?: $this->built_area ?: $this->size)
            : ($this->size ?: $this->land_area ?: $this->built_area);
    }

    /** The price as Taka for the budget filter (null: call for price or not a plain amount). */
    public function priceAmount(): ?int
    {
        return $this->price_amount;
    }

    public function priceLabel(): string
    {
        return $this->call_for_price || trim((string) $this->price) === '' ? __('site::common.call_for_price') : $this->price;
    }
}
