<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masterplans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->text('description');
            // The SVG layout; width/height are its viewBox size.
            $table->string('image_path')->default('');
            $table->unsignedInteger('image_width')->default(0);
            $table->unsignedInteger('image_height')->default(0);
            // data-plot keys of the layout's <g class="plan-plot"> groups, in document order.
            $table->json('layout_keys')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masterplans');
    }
};
