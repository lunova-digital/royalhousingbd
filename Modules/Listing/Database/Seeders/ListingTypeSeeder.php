<?php

namespace Modules\Listing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Listing\Models\ListingType;

/** The standard listing types; the admin can add, rename and reorder them. Safe to re-run. */
class ListingTypeSeeder extends Seeder
{
    public const TYPES = [
        'property' => 'Property',
        'plot' => 'Plot',
        'flat' => 'Flat',
        'resort' => 'Resort',
        'five-star-hotel' => 'Five Star Hotel',
    ];

    public function run(): void
    {
        $order = (int) ListingType::query()->max('sort_order');
        foreach (self::TYPES as $slug => $name) {
            ListingType::query()->firstOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => ++$order]);
        }
    }
}
