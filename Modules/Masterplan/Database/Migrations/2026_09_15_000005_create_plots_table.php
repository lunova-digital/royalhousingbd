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
            // The <g class="plan-plot"> of the layout SVG this row describes.
            $table->string('svg_key', 100);
            $table->string('number', 50);
            $table->string('size')->default('');
            $table->string('price')->default('');
            $table->string('position')->default('');
            $table->string('status', 20)->default('available')->index();
            $table->timestamps();
            $table->unique(['masterplan_id', 'svg_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plots');
    }
};
