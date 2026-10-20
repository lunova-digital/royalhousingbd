<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public accounts. Kept apart from `users` (the admin): a member has its own guard and can never reach /admin.
     * A member needs at least one login identifier (email or mobile), enforced by the app; everything else is optional.
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 20)->nullable()->unique(); // +8801XXXXXXXXX
            $table->string('password')->nullable(); // null: signed up with Google and has not set one
            $table->string('google_id')->nullable()->unique();
            $table->string('address', 500)->nullable();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('member_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // One-time codes for resetting the password of a mobile-only account (sent through SmsSender).
        Schema::create('phone_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('saved_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['member_id', 'listing_id']);
            $table->index('listing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_listings');
        Schema::dropIfExists('phone_otps');
        Schema::dropIfExists('member_password_reset_tokens');
        Schema::dropIfExists('members');
    }
};
