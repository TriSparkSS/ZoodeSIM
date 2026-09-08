<?php

namespace App\Services\Fraud;

use App\Models\User;
use App\Services\Fraud\Contracts\DeviceFraudServiceInterface;
use Illuminate\Validation\ValidationException;

class DeviceFraudService implements DeviceFraudServiceInterface
{
    public function assertCanRegister(?string $deviceId, ?string $ip, bool $applyingPromo): void
    {
        $this->assertIpAllowed($ip);

        if (! $applyingPromo) {
            return;
        }

        $normalized = $this->normalizeDeviceId($deviceId);

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'device_id' => __('api.promo.device_required'),
            ]);
        }

        $this->assertDeviceCanRedeemPromo($normalized);
    }

    public function assertDeviceCanRedeemPromo(string $deviceId, ?string $exceptUserId = null): void
    {
        $normalized = $this->normalizeDeviceId($deviceId);

        if ($normalized === null) {
            return;
        }

        $query = User::query()
            ->where('device_id', $normalized)
            ->whereHas('promoUsage');

        if ($exceptUserId !== null && $exceptUserId !== '') {
            $query->where('id', '!=', $exceptUserId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'device_id' => __('api.promo.device_already_used'),
            ]);
        }
    }

    protected function assertIpAllowed(?string $ip): void
    {
        if ($ip === null || $ip === '') {
            return;
        }

        $blocked = config('fraud.blocked_ips', []);

        if (in_array($ip, $blocked, true)) {
            throw ValidationException::withMessages([
                'ip' => __('api.promo.ip_blocked'),
            ]);
        }

        $max = max(1, (int) config('fraud.max_registrations_per_ip', 5));
        $hours = max(1, (int) config('fraud.ip_window_hours', 24));

        $count = User::query()
            ->where('registration_ip', $ip)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        if ($count >= $max) {
            throw ValidationException::withMessages([
                'ip' => __('api.promo.ip_limited'),
            ]);
        }
    }

    protected function normalizeDeviceId(?string $deviceId): ?string
    {
        $deviceId = is_string($deviceId) ? trim($deviceId) : '';

        return $deviceId !== '' ? $deviceId : null;
    }
}
