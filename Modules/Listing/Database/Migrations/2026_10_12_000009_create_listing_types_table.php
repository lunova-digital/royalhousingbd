<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Listing types become rows; listings.type keeps holding the slug. Existing types are carried over. */
    public function up(): void
    {
        Schema::create('listing_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('name', 60);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $order = 0;
        foreach (['property' => 'Property', 'plot' => 'Plot', 'flat' => 'Flat'] as $slug => $name) {
            DB::table('listing_types')->insert(['slug' => $slug, 'name' => $name, 'sort_order' => $order++, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (DB::table('listings')->distinct()->pluck('type') as $slug) {
            if (! DB::table('listing_types')->where('slug', $slug)->exists()) {
                DB::table('listing_types')->insert(['slug' => $slug, 'name' => ucwords(str_replace('-', ' ', $slug)), 'sort_order' => $order++, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_types');
    }
};
