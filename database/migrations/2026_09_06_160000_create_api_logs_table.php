<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 32);
            $table->string('service', 64);
            $table->string('method', 16);
            $table->string('endpoint', 512);
            $table->text('full_url');
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->unsignedInteger('response_time_ms')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('reference_type', 64)->nullable();
            $table->string('reference_id', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('service');
            $table->index('method');
            $table->index('response_status');
            $table->index('user_id');
            $table->index('reference_id');
            $table->index('created_at');
            $table->index('response_time_ms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
