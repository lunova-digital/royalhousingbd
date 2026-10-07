<?php

namespace Modules\Masterplan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The details of one `<g class="plan-plot">` of a masterplan's SVG, matched by svg_key. */
class Plot extends Model
{
    public const STATUSES = ['available', 'booked', 'sold'];

    protected $guarded = [];

    public function masterplan(): BelongsTo
    {
        return $this->belongsTo(Masterplan::class);
    }

    /** Quoted in the WhatsApp message (FR-MAP-008), e.g. "MAP-2/PLOT-A14". */
    public function reference(): string
    {
        return sprintf('MAP-%d/PLOT-%s', $this->masterplan_id, strtoupper($this->number));
    }
}
