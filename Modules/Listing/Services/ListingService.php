<?php

namespace Modules\Listing\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Listing\Models\Feature;
use Modules\Listing\Models\Listing;
use Modules\Listing\Models\ListingImage;
use Modules\Listing\Models\ListingType;
use Modules\Listing\Models\Location;
use Modules\Listing\Support\Price;
use Modules\Media\Services\DocumentStore;
use Modules\Media\Services\ImageStore;

class ListingService
{
    /**
     * Drives both the public grid (FR-LIST-002) and the admin table.
     *
     * `q` is a keyword search: every word must match the title, location,
     * description, type name, a feature name or the linked masterplan's name.
     * `features` are slugs the listing must all have. `budget_min` / `budget_max` are Taka
     * (0 = open); setting either leaves out listings without a numeric price (call for price).
     *
     * @param  array{type?: string, location?: string|string[], sort?: string, q?: string, features?: string[], budget_min?: int, budget_max?: int, published?: bool}  $filter
     */
    public function search(array $filter, int $perPage = 12, int $page = 1): LengthAwarePaginator
    {
        $query = Listing::query()->with(['images', 'features', 'masterplan.plots:id,masterplan_id,status']);
        // Newest first by default; price sorts leave "call for price" listings last.
        match ($filter['sort'] ?? 'newest') {
            'price_asc' => $query->orderByRaw('price_amount IS NULL')->orderBy('price_amount')->orderByDesc('id'),
            'price_desc' => $query->orderByRaw('price_amount IS NULL')->orderByDesc('price_amount')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
        if (! empty($filter['type'])) {
            $query->where('type', $filter['type']);
        }
        if (! empty($filter['published'])) {
            $query->published();
        }
        // One location or several (names as stored, i.e. the English ones)
        $locations = array_values(array_filter(array_map('trim', (array) ($filter['location'] ?? [])), fn ($l) => $l !== ''));
        if ($locations) {
            $query->whereIn('location', $locations);
        }
        foreach ($filter['features'] ?? [] as $slug) {
            $query->whereHas('features', fn (Builder $q) => $q->where('slug', $slug));
        }
        [$min, $max] = [Price::bound($filter['budget_min'] ?? 0), Price::bound($filter['budget_max'] ?? 0)];
        if ($min > 0 || $max > 0) {
            $query->whereNotNull('price_amount')
                ->when($min > 0, fn ($q) => $q->where('price_amount', '>=', $min))
                ->when($max > 0, fn ($q) => $q->where('price_amount', '<=', $max));
        }
        foreach (preg_split('/\s+/', mb_strtolower(trim($filter['q'] ?? ''))) ?: [] as $term) {
            if ($term === '') {
                continue;
            }
            $like = "%{$term}%";
            $typeSlugs = ListingType::registry()->filter(fn (ListingType $t) => str_contains(mb_strtolower($t->name), $term))->keys()->all();
            $query->where(function (Builder $q) use ($like, $typeSlugs) {
                $q->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(location) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$like])
                    ->orWhereHas('features', fn (Builder $f) => $f->whereRaw('LOWER(name) LIKE ?', [$like]))
                    ->orWhereHas('masterplan', fn (Builder $m) => $m->whereRaw('LOWER(name) LIKE ?', [$like]));
                if ($typeSlugs) {
                    $q->orWhereIn('type', $typeSlugs);
                }
            });
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /** Every location, with how many published listings (of the type) are in it, for the filter. */
    public function locations(?string $type = null): array
    {
        return Location::query()->ordered()
            ->withCount(['listings' => fn (Builder $q) => $q->published()->when($type, fn ($q) => $q->where('type', $type))])
            ->get()->all();
    }

    /** Features used by at least one published listing (of the type), with how many, for the filter. */
    public function filterFeatures(?string $type = null): array
    {
        return Feature::query()->ordered()
            ->whereHas('listings', fn (Builder $q) => $q->published()->when($type, fn ($q) => $q->where('type', $type)))
            ->withCount(['listings' => fn (Builder $q) => $q->published()->when($type, fn ($q) => $q->where('type', $type))])
            ->get()->all();
    }

    public function findPublished(string $type, string $slug): ?Listing
    {
        return Listing::query()->with(['images', 'features', 'masterplan.plots:id,masterplan_id,status'])->published()->where('type', $type)->where('slug', $slug)->first();
    }

    /** @param  int[]  $featureIds */
    public function create(array $attributes, array $featureIds = []): Listing
    {
        $attributes['slug'] = $this->uniqueSlug($attributes['title']);
        $attributes = $this->withPriceAmount($attributes);

        return DB::transaction(function () use ($attributes, $featureIds) {
            $listing = Listing::query()->create($attributes);
            $listing->features()->sync($featureIds);

            return $listing->load(['images', 'features']);
        });
    }

    /** @param  int[]  $featureIds */
    public function update(Listing $listing, array $attributes, array $featureIds = []): Listing
    {
        if ($listing->title !== $attributes['title']) {
            $attributes['slug'] = $this->uniqueSlug($attributes['title'], $listing->id);
        }
        $attributes = $this->withPriceAmount($attributes);
        DB::transaction(function () use ($listing, $attributes, $featureIds) {
            $listing->update($attributes);
            $listing->features()->sync($featureIds);
        });

        return $listing->load(['images', 'features']);
    }

    public function delete(Listing $listing): void
    {
        foreach ($listing->images as $image) {
            ImageStore::delete($image->path, $image->thumb_path);
        }
        DocumentStore::delete($listing->brochure_path);
        $listing->delete();
    }

    /** Store the PDF brochure, replacing the current one. */
    public function setBrochure(Listing $listing, UploadedFile $file): Listing
    {
        $stored = DocumentStore::storePdf($file, 'file', 'brochures');
        DocumentStore::delete($listing->brochure_path);
        $listing->update(['brochure_path' => $stored['path'], 'brochure_size' => $stored['size']]);

        return $listing;
    }

    public function deleteBrochure(Listing $listing): Listing
    {
        DocumentStore::delete($listing->brochure_path);
        $listing->update(['brochure_path' => '', 'brochure_size' => 0]);

        return $listing;
    }

    /**
     * Append uploaded files to the gallery, after the existing ones.
     *
     * @param  UploadedFile[]  $files
     */
    public function addImages(Listing $listing, array $files): array
    {
        $next = $listing->images()->count();
        $added = [];
        foreach ($files as $file) {
            $stored = ImageStore::store($file, 'files', 'listings', ImageStore::MAX_WIDTH, thumb: true);
            $added[] = $listing->images()->create([
                'path' => $stored['path'],
                'thumb_path' => $stored['thumb_path'],
                'sort_order' => $next++,
            ]);
        }

        return $added;
    }

    public function deleteImage(ListingImage $image): void
    {
        ImageStore::delete($image->path, $image->thumb_path);
        $image->delete();
    }

    /** Set sort_order to the position of each id; the first becomes the cover. */
    public function reorderImages(Listing $listing, array $ids): void
    {
        DB::transaction(function () use ($listing, $ids) {
            foreach (array_values($ids) as $i => $id) {
                $listing->images()->where('id', $id)->update(['sort_order' => $i]);
            }
        });
    }

    /** The admin-selected homepage row (FR-HOME-002), in order. */
    public function featured(bool $publishedOnly = true): array
    {
        return Listing::query()->with(['images', 'features', 'masterplan.plots:id,masterplan_id,status'])->where('featured', true)
            ->when($publishedOnly, fn ($q) => $q->published())
            ->orderBy('featured_order')->get()->all();
    }

    /** Replace the featured row with $ids, in that order. */
    public function setFeatured(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            Listing::query()->where('featured', true)->update(['featured' => false, 'featured_order' => 0]);
            foreach (array_values($ids) as $i => $id) {
                Listing::query()->where('id', $id)->update(['featured' => true, 'featured_order' => $i]);
            }
        });
    }

    /** The budget amount: as entered, else read from the price text; none while "call for price". */
    private function withPriceAmount(array $attributes): array
    {
        if (! empty($attributes['call_for_price'])) {
            $attributes['price_amount'] = null;
        } elseif (empty($attributes['price_amount'])) {
            $attributes['price_amount'] = Price::parse($attributes['price'] ?? '');
        }

        return $attributes;
    }

    private function uniqueSlug(string $title, ?int $selfId = null): string
    {
        $base = Str::slug($title) ?: 'listing';
        $slug = $base;
        for ($i = 2; ; $i++) {
            $taken = Listing::query()->where('slug', $slug)
                ->when($selfId, fn ($q) => $q->where('id', '!=', $selfId))
                ->exists();
            if (! $taken) {
                return $slug;
            }
            $slug = "{$base}-{$i}";
        }
    }
}
