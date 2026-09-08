<?php

use App\Models\Partner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0)->after('phone');
            $table->unsignedInteger('bonus_mb')->default(0)->after('balance');
        });

        Schema::create('transaction_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('value')->default(0);
        });

        DB::table('transaction_counters')->insert(['id' => 1, 'value' => 0]);

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_id', 32)->unique();
            $table->string('transactable_type');
            $table->uuid('transactable_id');
            $table->string('type', 16);
            $table->string('category', 32);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('status', 16)->default('completed');
            $table->string('reference_type', 64)->nullable();
            $table->string('reference_id', 64)->nullable();
            $table->uuid('promo_code_id')->nullable();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['transactable_type', 'transactable_id']);
            $table->index(['type', 'category']);
            $table->index(['status', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('promo_code_id');
        });

        $this->migratePartnerTransactions();

        Schema::dropIfExists('partner_transactions');
    }

    public function down(): void
    {
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

        Schema::dropIfExists('transactions');
        Schema::dropIfExists('transaction_counters');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['balance', 'bonus_mb']);
        });
    }

    protected function migratePartnerTransactions(): void
    {
        if (! Schema::hasTable('partner_transactions')) {
            return;
        }

        $rows = DB::table('partner_transactions')->orderBy('created_at')->orderBy('id')->get();
        $next = 0;

        foreach ($rows as $row) {
            $next++;
            $amount = (string) $row->amount;
            $after = (string) $row->balance_after;
            $before = $row->type === 'credit'
                ? bcsub($after, $amount, 2)
                : bcadd($after, $amount, 2);

            DB::table('transactions')->insert([
                'id' => (string) Str::uuid(),
                'transaction_id' => 'TXN-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT),
                'transactable_type' => Partner::class,
                'transactable_id' => $row->partner_id,
                'type' => $row->type,
                'category' => $row->category,
                'amount' => $row->amount,
                'balance_before' => $before,
                'balance_after' => $row->balance_after,
                'currency' => $row->currency ?: 'USD',
                'status' => 'completed',
                'reference_type' => $row->reference_type,
                'reference_id' => $row->reference_id,
                'promo_code_id' => $row->category === 'promo_reward' && $row->reference_type === 'promo_usage'
                    ? null
                    : null,
                'description' => $row->description,
                'meta' => null,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        DB::table('transaction_counters')->where('id', 1)->update(['value' => $next]);
    }
};
