<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_sessions', function (Blueprint $table) {
            $table->id();
            $table->morphs('authenticatable');
            $table->string('guard', 50);
            $table->string('session_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_label')->nullable();
            $table->timestamp('login_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(
                ['authenticatable_type', 'authenticatable_id', 'revoked_at'],
                'auth_sessions_auth_revoked_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_sessions');
    }
};
