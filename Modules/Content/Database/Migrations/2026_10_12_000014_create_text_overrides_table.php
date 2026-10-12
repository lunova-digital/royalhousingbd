<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Admin replacements for the public site's translated copy, one row per language and key ("home::index.faq"). */
    public function up(): void
    {
        Schema::create('text_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5);
            $table->string('key', 191);
            $table->text('value');
            $table->timestamps();
            $table->unique(['locale', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('text_overrides');
    }
};
