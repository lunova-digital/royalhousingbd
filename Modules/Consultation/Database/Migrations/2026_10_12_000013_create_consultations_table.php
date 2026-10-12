<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->string('name', 80);
            $table->string('phone', 30);
            $table->string('email', 120)->nullable();
            $table->string('mode', 20);          // phone | whatsapp | office | video
            $table->string('topic', 60)->nullable();
            $table->text('message')->nullable();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('scheduled_at')->index();
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('status', 20)->index(); // pending | confirmed | completed | cancelled
            $table->string('locale', 5)->default('en');
            $table->string('source_url', 500)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
