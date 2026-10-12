<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** An optional PDF brochure per listing, offered as a download on the detail page. */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('brochure_path')->default('')->after('video_url');
            $table->unsignedInteger('brochure_size')->default(0)->after('brochure_path');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['brochure_path', 'brochure_size']);
        });
    }
};
