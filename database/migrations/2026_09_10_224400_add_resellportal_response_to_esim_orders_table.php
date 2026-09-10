<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esim_orders', function (Blueprint $table) {
            $table->json('resellportal_response')->nullable()->after('resellportal_service_id');
        });
    }

    public function down(): void
    {
        Schema::table('esim_orders', function (Blueprint $table) {
            $table->dropColumn('resellportal_response');
        });
    }
};
