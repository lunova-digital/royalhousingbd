<?php

namespace Modules\Listing\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Listing\Models\Listing;
use Modules\Listing\Models\ListingImage;
use Modules\Media\Services\ImageStore;

class ListingService
{
    /**
     * Drives both the public grid (FR-LIST-002) and the admin table.
     *
     * @param  array{type?: string, location?: string, q?: string, published?: bool}  $filter
     */
    public function search(array $filter, int $perPage = 12, int $page = 1): LengthAwarePaginator
    {
        $query = Listing::query()->with(['images', 'masterplan.plots'])->orderByDesc('id');
        if (! empty($filter['type'])) {
            $query->where('type', $filter['type']);
        }
        if (! empty($filter['published'])) {
            $query->published();
        }
        if (($location = trim($filter['location'] ?? '')) !== '') {
            $query->where('location', $location);
        }
        if (($term = trim($filter['q'] ?? '')) !== '') {
            $like = '%'.mb_strtolower($term).'%';
            $query->where(fn (Builder $q) => $q->whereRaw('LOWER(title) LIKE ?', [$like])->orWhereRaw('LOWER(location) LIKE ?', [$like]));
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /** Distinct locations of published listings, for the filter dropdown. */
    public function locations(?string $type = null): array
    {
        return Listing::query()->published()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->distinct()->orderBy('location')->pluck('location')->all();
    }

    public function findPublished(string $type, string $slug): ?Listing
    {
        return Listing::query()->with(['images', 'masterplan.plots'])->published()->where('type', $type)->where('slug', $slug)->first();
    }

    public function create(array $attributes): Listing
    {
        $attributes['slug'] = $this->uniqueSlug($attributes['title']);

        return Listing::query()->create($attributes)->load('images');
    }

    public function update(Listing $listing, array $attributes): Listing
    {
        if ($listing->title !== $attributes['title']) {
            $attributes['slug'] = $this->uniqueSlug($attributes['title'], $listing->id);
        }
        $listing->update($attributes);

        return $listing->load('images');
    }

    public function delete(Listing $listing): void
    {
        foreach ($listing->images as $image) {
            ImageStore::delete($image->path, $image->thumb_path);
        }
        $listing->delete();
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
        return Listing::query()->with(['images', 'masterplan.plots'])->where('featured', true)
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
