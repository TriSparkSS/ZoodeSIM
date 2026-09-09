<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esim_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('idempotency_key');
            $table->string('resellportal_client_id')->nullable();
            $table->string('package_code');
            $table->string('package_name');
            $table->string('package_location')->nullable();
            $table->string('package_data_volume')->nullable();
            $table->unsignedInteger('package_duration')->nullable();
            $table->decimal('provider_cost', 15, 2);
            $table->decimal('customer_price', 15, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('payment_status')->default('pending');
            $table->string('order_status')->default('pending_payment');
            $table->string('resellportal_service_id')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'order_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_orders');
    }
};
