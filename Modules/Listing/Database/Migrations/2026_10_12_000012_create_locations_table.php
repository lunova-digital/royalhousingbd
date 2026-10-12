<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Locations become an admin-managed list. listings.location stays as a copy of the
     * location's name (the public filter, search and every page read it) and
     * listings.location_id says which row it came from; the model keeps them in sync.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('location')->constrained()->nullOnDelete();
        });

        foreach (DB::table('listings')->select('id', 'location')->get() as $listing) {
            $name = trim((string) $listing->location);
            if ($name === '') {
                continue;
            }
            $id = DB::table('locations')->where('name', $name)->value('id')
                ?? DB::table('locations')->insertGetId(['name' => $name, 'slug' => $this->uniqueSlug($name), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('listings')->where('id', $listing->id)->update(['location_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });
        Schema::dropIfExists('locations');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'location';
        $slug = $base;
        for ($i = 2; DB::table('locations')->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
};
