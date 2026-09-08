<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('payout_method', 32)->nullable()->after('total_earned');
            $table->string('payout_details', 255)->nullable()->after('payout_method');
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('payout_details', 255)->nullable()->after('method');
            $table->text('admin_note')->nullable()->after('status');
            $table->unsignedBigInteger('processed_by')->nullable()->after('admin_note');
            $table->timestamp('rejected_at')->nullable()->after('completed_at');
            $table->index(['partner_id', 'status']);
        });

        Schema::create('partner_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('partner_id');
            $table->string('type', 16);
            $table->string('category', 32);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('reference_type', 64)->nullable();
            $table->string('reference_id', 64)->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
            $table->index(['partner_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_transactions');

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropIndex(['partner_id', 'status']);
            $table->dropColumn(['payout_details', 'admin_note', 'processed_by', 'rejected_at']);
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn(['payout_method', 'payout_details']);
        });
    }
};
