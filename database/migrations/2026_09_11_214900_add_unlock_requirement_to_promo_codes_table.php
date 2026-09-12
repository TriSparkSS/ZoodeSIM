<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->unsignedInteger('unlock_requirement')->nullable()->after('max_usage');
            $table->timestamp('unlocked_at')->nullable()->after('unlock_requirement');
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn(['unlock_requirement', 'unlocked_at']);
        });
    }
};
