<?php

namespace Modules\Masterplan\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feature 2: an interactive project layout (FR-MAP-*). A masterplan is an
 * SVG (image_path) in which every `<g class="plan-plot">` is a plot;
 * layout_keys lists those groups' keys and a Plot row holds the details the
 * admin filled in for one of them.
 */
class Masterplan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'image_width' => 'integer', 'image_height' => 'integer', 'layout_keys' => 'array'];
    }

    public function plots(): HasMany
    {
        return $this->hasMany(Plot::class)->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function url(): string
    {
        return "/map/{$this->slug}";
    }

    /** @return array<string, int> plots per status, for the legend */
    public function statusCounts(): array
    {
        $counts = array_fill_keys(Plot::STATUSES, 0);
        foreach ($this->plots as $plot) {
            $counts[$plot->status] = ($counts[$plot->status] ?? 0) + 1;
        }

        return $counts;
    }
}
