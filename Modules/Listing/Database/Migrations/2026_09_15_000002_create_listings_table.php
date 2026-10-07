<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('title', 200);
            $table->string('slug', 220);
            $table->string('location')->index();
            $table->string('price')->default('');
            $table->boolean('call_for_price')->default(false);
            // Property
            $table->string('land_area')->default('');
            $table->string('built_area')->default('');
            // Plot (katha/sqft) and Flat (sqft)
            $table->string('size')->default('');
            // Flat
            $table->unsignedInteger('bedrooms')->default(0);
            $table->unsignedInteger('baths')->default(0);
            $table->string('floor')->default('');
            $table->string('video_url')->default('');
            $table->json('facilities');
            $table->text('description');
            $table->boolean('featured')->default(false)->index();
            $table->unsignedInteger('featured_order')->default(0);
            $table->boolean('published')->default(false);
            $table->timestamps();
            $table->unique(['type', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
