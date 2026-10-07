<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A plot-development listing can point at its masterplan so cards show live availability. */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('masterplan_id')->nullable()->after('published')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('masterplan_id');
        });
    }
};
