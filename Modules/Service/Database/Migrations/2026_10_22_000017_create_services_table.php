<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title', 120);
            $table->string('title_bn', 120)->default('');
            $table->string('summary', 300)->default('');
            $table->string('summary_bn', 300)->default('');
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->json('benefits')->nullable();
            $table->json('benefits_bn')->nullable();
            $table->string('image')->default('');
            $table->json('gallery')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
