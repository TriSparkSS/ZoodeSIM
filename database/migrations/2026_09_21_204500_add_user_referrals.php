<?php

use App\Models\PromoCode;
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
            $table->string('referral_code')->nullable()->unique()->after('auth_provider');
            $table->foreignUuid('referred_by_user_id')
                ->nullable()
                ->after('referral_code')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('user_referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('referred_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('referrer_amount', 15, 2);
            $table->decimal('referred_amount', 15, 2);
            $table->string('currency', 8)->default('USD');
            $table->timestamps();

            $table->index('referrer_id');
        });

        $taken = PromoCode::query()->pluck('code')->flip()->all();

        DB::table('users')->whereNull('referral_code')->orderBy('created_at')->each(function (object $user) use (&$taken) {
            $code = $this->uniqueCode($taken);
            $taken[$code] = true;

            DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_referrals');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_user_id');
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }

    /**
     * @param  array<string, mixed>  $taken
     */
    protected function uniqueCode(array $taken): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $candidate = strtoupper(Str::random(8));

            if (! isset($taken[$candidate]) && ! DB::table('users')->where('referral_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        return strtoupper(Str::random(12));
    }
};
