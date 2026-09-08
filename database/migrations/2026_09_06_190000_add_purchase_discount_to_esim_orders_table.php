<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esim_orders', function (Blueprint $table) {
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('customer_price');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_percentage');
            $table->decimal('charged_amount', 15, 2)->nullable()->after('discount_amount');
        });

        DB::table('esim_orders')
            ->whereNull('charged_amount')
            ->update(['charged_amount' => DB::raw('customer_price')]);
    }

    public function down(): void
    {
        Schema::table('esim_orders', function (Blueprint $table) {
            $table->dropColumn(['discount_percentage', 'discount_amount', 'charged_amount']);
        });
    }
};
