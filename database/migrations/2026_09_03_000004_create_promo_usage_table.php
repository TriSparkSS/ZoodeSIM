<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_usage', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('promo_code_id');
            $table->uuid('user_id');
            $table->uuid('partner_id');

            $table->integer('bonus_mb_given');
            $table->decimal('partner_reward', 15, 2);

            $table->timestamp('used_at');

            $table->timestamps();

            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_usage');
    }
};

