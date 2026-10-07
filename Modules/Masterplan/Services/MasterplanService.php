<?php

namespace Modules\Masterplan\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Masterplan\Models\Masterplan;
use Modules\Media\Services\ImageStore;

class MasterplanService
{
    /**
     * Step 1 + 2 of adding a masterplan: the SVG layout (validated and
     * normalised first, so a bad file creates nothing) and the project info.
     */
    public function create(array $attributes, string $svg): Masterplan
    {
        $layout = LayoutSvg::normalise($svg, 'layout');
        $attributes['slug'] = $this->uniqueSlug($attributes['name']);

        return Masterplan::query()->create($attributes + $this->layoutAttributes($layout))->load('plots');
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
     * Replace the layout. Plot details are keyed by the SVG group they belong
     * to, so details for groups missing from the new file are dropped.
     */
    public function setLayout(Masterplan $masterplan, string $svg): Masterplan
    {
        $layout = LayoutSvg::normalise($svg, 'layout');
        $old = $masterplan->image_path;

        DB::transaction(function () use ($masterplan, $layout) {
            $masterplan->update($this->layoutAttributes($layout));
            $masterplan->plots()->whereNotIn('svg_key', $layout['keys'])->delete();
        });
        ImageStore::delete($old);
        $this->stampPlotIds($masterplan->refresh());

        return $masterplan->load('plots');
    }

    /**
     * The builder's bulk save: one row per SVG plot that has details. Rows
     * are matched by their svg_key; keys no longer present are deleted.
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
                ];
                $plot = $masterplan->plots()->updateOrCreate(['svg_key' => $in['svg_key']], $attributes);
                $keep[] = $plot->id;
            }
            $masterplan->plots()->whereNotIn('id', $keep)->delete();
        });
        $this->stampPlotIds($masterplan);

        return $masterplan->refresh()->load('plots');
    }

    /** Write each plot's row id into its group of the stored SVG (data-plot-id). */
    private function stampPlotIds(Masterplan $masterplan): void
    {
        $disk = Storage::disk('public');
        if (! $masterplan->image_path || ! $disk->exists($masterplan->image_path)) {
            return;
        }
        $ids = $masterplan->plots()->pluck('id', 'svg_key')->all();
        $disk->put($masterplan->image_path, LayoutSvg::withPlotIds($disk->get($masterplan->image_path), $ids));
    }

    /**
     * @param  array{svg: string, width: int, height: int, keys: string[]}  $layout
     */
    private function layoutAttributes(array $layout): array
    {
        $disk = Storage::disk('public');
        $folder = 'uploads/masterplans/'.now()->format('Y/m');
        $disk->makeDirectory($folder);
        $path = $folder.'/'.time().Str::lower(Str::random(16)).'.svg';
        $disk->put($path, $layout['svg']);

        return [
            'image_path' => $path,
            'image_width' => $layout['width'],
            'image_height' => $layout['height'],
            'layout_keys' => $layout['keys'],
        ];
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
