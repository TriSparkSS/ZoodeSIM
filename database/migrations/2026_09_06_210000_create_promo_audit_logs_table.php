<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('promo_code_id')->nullable();
            $table->uuid('partner_id')->nullable();
            $table->nullableUuidMorphs('actor');
            $table->string('action', 32);
            $table->string('code', 32)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
            $table->index('code');
            $table->index('created_at');
            $table->index(['partner_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_audit_logs');
    }
};
