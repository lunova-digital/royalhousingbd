<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masterplan_id')->constrained()->cascadeOnDelete();
            $table->string('number', 50);
            $table->string('size')->default('');
            $table->string('price')->default('');
            $table->string('position')->default('');
            $table->string('status', 20)->default('available')->index();
            // Polygon over the image, in pixel coordinates: [{x, y}, ...]
            $table->json('polygon');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plots');
    }
};
