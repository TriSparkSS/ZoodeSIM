<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->string('bonus_type')->default('mb')->after('bonus_mb');
            $table->decimal('bonus_amount', 15, 2)->nullable()->after('bonus_type');
        });

        Schema::table('promo_usage', function (Blueprint $table) {
            $table->string('bonus_type')->default('mb')->after('bonus_mb_given');
            $table->decimal('bonus_amount', 15, 2)->nullable()->after('bonus_type');
        });

        DB::table('promo_codes')->whereNull('bonus_amount')->update([
            'bonus_type' => 'mb',
            'bonus_amount' => DB::raw('bonus_mb'),
        ]);

        DB::table('promo_usage')->whereNull('bonus_amount')->update([
            'bonus_type' => 'mb',
            'bonus_amount' => DB::raw('bonus_mb_given'),
        ]);
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn(['bonus_type', 'bonus_amount']);
        });

        Schema::table('promo_usage', function (Blueprint $table) {
            $table->dropColumn(['bonus_type', 'bonus_amount']);
        });
    }
};
