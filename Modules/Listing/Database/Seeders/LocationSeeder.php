<?php

namespace Modules\Listing\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Listing\Models\Location;

/** The areas the client works in; the admin can add, rename and remove them. Safe to re-run. */
class LocationSeeder extends Seeder
{
    public const LOCATIONS = [
        'Narayanganj', 'Malibagh', 'Uttara', 'Rampura', 'Eskaton', 'Cox\'s Bazar', 'Mirpur', 'Hasara (Munshiganj)',
    ];

    public function run(): void
    {
        foreach (self::LOCATIONS as $name) {
            Location::query()->firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
        }
    }
}
