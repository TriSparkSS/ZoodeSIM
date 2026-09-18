<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('firebase_uid')->nullable()->unique()->after('email');
            $table->string('auth_provider', 32)->default('password')->after('firebase_uid');
        });

        DB::table('users')->whereNull('auth_provider')->update(['auth_provider' => 'password']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['firebase_uid']);
            $table->dropColumn(['firebase_uid', 'auth_provider']);
            $table->string('phone')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
