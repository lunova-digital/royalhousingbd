<?php

namespace Modules\Masterplan\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Masterplan\Models\Masterplan;
use Modules\Masterplan\Models\Plot;
use Modules\Media\Services\ImageStore;

class MasterplanService
{
    public function create(array $attributes): Masterplan
    {
        $attributes['slug'] = $this->uniqueSlug($attributes['name']);

        return Masterplan::query()->create($attributes)->load('plots');
    }

    public function update(Masterplan $masterplan, array $attributes): Masterplan
    {
        if ($masterplan->name !== $attributes['name']) {
            $attributes['slug'] = $this->uniqueSlug($attributes['name'], $masterplan->id);
        }
        $masterplan->update($attributes);

        return $masterplan->load('plots');
    }

    public function delete(Masterplan $masterplan): void
    {
        ImageStore::delete($masterplan->image_path);
        $masterplan->delete();
    }

    /**
     * Replace the layout image. Plot polygons are pixel coordinates of the
     * stored image, so a different-sized image misplaces them; the builder
     * warns and the admin redraws.
     */
    public function setImage(Masterplan $masterplan, UploadedFile $file): Masterplan
    {
        $stored = ImageStore::store($file, 'file', 'masterplans', ImageStore::MAX_PLAN_WIDTH);
        ImageStore::delete($masterplan->image_path);
        $masterplan->update([
            'image_path' => $stored['path'],
            'image_width' => $stored['width'],
            'image_height' => $stored['height'],
        ]);

        return $masterplan->load('plots');
    }

    /**
     * The builder's bulk save: rows with an id are updated, rows without are
     * created, and rows no longer present are deleted.
     */
    public function savePlots(Masterplan $masterplan, array $plots): Masterplan
    {
        DB::transaction(function () use ($masterplan, $plots) {
            $keep = [];
            foreach ($plots as $in) {
                $attributes = [
                    'number' => trim($in['number']),
                    'size' => trim((string) ($in['size'] ?? '')),
                    'price' => trim((string) ($in['price'] ?? '')),
                    'position' => trim((string) ($in['position'] ?? '')),
                    'status' => $in['status'],
                    'polygon' => array_map(fn ($p) => ['x' => (float) $p['x'], 'y' => (float) $p['y']], $in['polygon']),
                ];
                $existing = ! empty($in['id']) ? $masterplan->plots()->find($in['id']) : null;
                if ($existing) {
                    $existing->update($attributes);
                    $keep[] = $existing->id;
                } else {
                    $keep[] = $masterplan->plots()->create($attributes)->id;
                }
            }
            $masterplan->plots()->whereNotIn('id', $keep)->delete();
        });

        return $masterplan->refresh()->load('plots');
    }

    private function uniqueSlug(string $name, ?int $selfId = null): string
    {
        $base = Str::slug($name) ?: 'map';
        $slug = $base;
        for ($i = 2; ; $i++) {
            $taken = Masterplan::query()->where('slug', $slug)
                ->when($selfId, fn ($q) => $q->where('id', '!=', $selfId))
                ->exists();
            if (! $taken) {
                return $slug;
            }
            $slug = "{$base}-{$i}";
        }
    }
}
