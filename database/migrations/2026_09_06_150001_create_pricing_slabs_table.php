<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_slabs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2);
            $table->decimal('percentage', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();

            $table->index(['is_active', 'priority', 'min_amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_slabs');
    }
};
