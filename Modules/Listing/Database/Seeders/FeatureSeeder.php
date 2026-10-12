<?php

namespace Modules\Listing\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Listing\Models\Feature;

/** A starter set of amenities; the admin can add, rename and remove them. Safe to re-run. */
class FeatureSeeder extends Seeder
{
    public const FEATURES = [
        'Lift', 'Parking', 'Generator', 'Gas connection', '24/7 security', 'CCTV',
        'Swimming pool', 'Gym', 'Rooftop garden', 'Community hall',
    ];

    public function run(): void
    {
        foreach (self::FEATURES as $name) {
            Feature::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
