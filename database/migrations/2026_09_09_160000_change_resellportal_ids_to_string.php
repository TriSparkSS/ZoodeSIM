<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->isStringColumn('users', 'resellportal_client_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('resellportal_client_id')->nullable()->unique()->change();
        });

        Schema::table('esim_orders', function (Blueprint $table) {
            $table->string('resellportal_client_id')->nullable()->change();
            $table->string('resellportal_service_id')->nullable()->change();
        });

        Schema::table('esim_order_details', function (Blueprint $table) {
            $table->string('service_id')->change();
        });
    }

    public function down(): void
    {
        if (! $this->isStringColumn('users', 'resellportal_client_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('resellportal_client_id')->nullable()->unique()->change();
        });

        Schema::table('esim_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('resellportal_client_id')->nullable()->change();
            $table->unsignedBigInteger('resellportal_service_id')->nullable()->change();
        });

        Schema::table('esim_order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->change();
        });
    }

    protected function isStringColumn(string $table, string $column): bool
    {
        $type = Schema::getColumnType($table, $column);

        return in_array($type, ['string', 'varchar', 'nvarchar', 'text'], true);
    }
};
