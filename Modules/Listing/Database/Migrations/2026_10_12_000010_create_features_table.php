<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** The free-text facilities list becomes a many-to-many with admin-managed features. */
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });
        Schema::create('feature_listing', function (Blueprint $table) {
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->primary(['feature_id', 'listing_id']);
        });

        foreach (DB::table('listings')->select('id', 'facilities')->get() as $listing) {
            foreach (json_decode($listing->facilities ?: '[]', true) ?: [] as $name) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }
                $slug = Str::slug($name) ?: 'feature';
                $id = DB::table('features')->where('slug', $slug)->value('id')
                    ?? DB::table('features')->insertGetId(['name' => $name, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('feature_listing')->insertOrIgnore(['feature_id' => $id, 'listing_id' => $listing->id]);
            }
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('facilities');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->json('facilities')->nullable();
        });
        Schema::dropIfExists('feature_listing');
        Schema::dropIfExists('features');
    }
};
