<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Bangla (or any non-English) version of admin-entered content: listing titles, locations,
     * features, announcements, hero text… One row per group + key + field + locale; English stays in
     * the record itself, so a missing row simply falls back to it.
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('group', 32);
            $table->string('key', 191);
            $table->string('field', 32)->default('value');
            $table->string('locale', 5)->default('bn');
            $table->text('value');
            $table->timestamps();
            $table->unique(['group', 'key', 'field', 'locale']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
