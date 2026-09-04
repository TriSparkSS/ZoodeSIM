<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('partner_id');

            $table->decimal('amount', 15, 2);
            $table->string('method'); // card / payme / click / crypto
            $table->string('status'); // pending / processing / completed / failed

            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};

