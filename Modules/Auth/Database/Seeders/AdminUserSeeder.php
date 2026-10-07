<?php

namespace Modules\Auth\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The single admin account (FR-ADM-001). Email and password come from
 * ADMIN_EMAIL / ADMIN_PASSWORD (config/app.php); change them before go-live.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => config('app.admin.email')],
            ['name' => 'Admin', 'password' => config('app.admin.password')],
        );
    }
}
