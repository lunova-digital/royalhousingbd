<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laramod\Facades\Seeder as ModuleSeeders;

class DatabaseSeeder extends Seeder
{
    /**
     * `php artisan db:seed` runs what the modules need (the admin account) and,
     * outside production, the demo content so every page has something to show.
     */
    public function run(): void
    {
        $this->call(ModuleSeeders::seeders());
        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
