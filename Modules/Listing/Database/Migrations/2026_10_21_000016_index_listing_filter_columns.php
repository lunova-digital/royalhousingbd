<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Columns the public grid filters and sorts on (published + type; price_amount is indexed by its own migration). */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->index(['published', 'type'], 'listings_published_type_index');
        });
        Schema::table('masterplans', function (Blueprint $table) {
            $table->index('published');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex('listings_published_type_index');
        });
        Schema::table('masterplans', function (Blueprint $table) {
            $table->dropIndex(['published']);
        });
    }
};
