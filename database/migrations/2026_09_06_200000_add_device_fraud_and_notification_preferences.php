<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('device_id', 128)->nullable()->after('bonus_mb');
            $table->string('registration_ip', 45)->nullable()->after('device_id');
            $table->index('device_id');
            $table->index('registration_ip');
        });

        Schema::table('promo_usage', function (Blueprint $table) {
            $table->string('device_id', 128)->nullable()->after('used_at');
            $table->string('ip_address', 45)->nullable()->after('device_id');
            $table->index('device_id');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->boolean('email_notifications')->default(true)->after('payout_details');
            $table->boolean('telegram_notifications')->default(false)->after('email_notifications');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['device_id']);
            $table->dropIndex(['registration_ip']);
            $table->dropColumn(['device_id', 'registration_ip']);
        });

        Schema::table('promo_usage', function (Blueprint $table) {
            $table->dropIndex(['device_id']);
            $table->dropColumn(['device_id', 'ip_address']);
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn(['email_notifications', 'telegram_notifications']);
        });
    }
};
