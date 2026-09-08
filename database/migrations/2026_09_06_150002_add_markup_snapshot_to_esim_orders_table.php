<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esim_orders', function (Blueprint $table) {
            $table->decimal('markup_percentage', 5, 2)->nullable()->after('provider_cost');
            $table->decimal('markup_amount', 15, 2)->nullable()->after('markup_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('esim_orders', function (Blueprint $table) {
            $table->dropColumn(['markup_percentage', 'markup_amount']);
        });
    }
};
