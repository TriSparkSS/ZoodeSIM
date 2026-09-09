<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esim_order_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('esim_order_id');
            $table->string('service_id');
            $table->string('iccid')->nullable();
            $table->text('qr_code_url')->nullable();
            $table->text('activation_url')->nullable();
            $table->string('esim_status')->nullable();
            $table->timestamps();

            $table->foreign('esim_order_id')->references('id')->on('esim_orders')->cascadeOnDelete();
            $table->unique('esim_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_order_details');
    }
};
