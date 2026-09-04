<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->index();
            $table->string('phone');

            // selected platforms: instagram/telegram/tiktok/youtube
            $table->json('platforms');

            // contact handles/links used by partner (Telegram/Instagram)
            $table->string('instagram')->nullable();
            $table->string('telegram')->nullable();

            // additional social links (kept for compatibility with the current Apply form)
            $table->string('tiktok')->nullable();
            $table->string('youtube')->nullable();

            $table->string('followers');
            $table->string('niche');
            $table->string('country')->nullable();
            $table->text('about')->nullable();

            // pending / approved / rejected
            $table->string('status')->default('pending')->index();
            $table->uuid('partner_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_applications');
    }
};

