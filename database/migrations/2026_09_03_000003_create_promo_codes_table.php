<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('partner_id');
            $table->string('code')->unique();

            // MB granted to user on registration
            $table->integer('bonus_mb');

            // $ credited to partner per successful registration
            $table->decimal('partner_reward', 15, 2);

            // standard / premium / seasonal / single
            $table->string('type')->default('standard');

            $table->timestamp('expires_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->integer('max_usage')->nullable();

            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};

