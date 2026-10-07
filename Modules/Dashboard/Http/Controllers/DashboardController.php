<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Listing\Models\Listing;
use Modules\Masterplan\Models\Masterplan;
use Modules\Masterplan\Models\Plot;

/** Basic counts for the admin landing screen (FR-ADM-002). */
class DashboardController
{
    public function index(): JsonResponse
    {
        $listings = Listing::query()->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type');
        $plots = Plot::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return response()->json([
            'listings' => collect(Listing::TYPES)->mapWithKeys(fn ($t) => [$t => (int) ($listings[$t] ?? 0)]),
            'maps' => Masterplan::query()->count(),
            'plots' => collect(Plot::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($plots[$s] ?? 0)]),
        ]);
    }
}
