<?php

namespace App\Services\Fraud\Contracts;

interface DeviceFraudServiceInterface
{
    public function assertCanRegister(?string $deviceId, ?string $ip, bool $applyingPromo): void;

    public function assertDeviceCanRedeemPromo(string $deviceId, ?string $exceptUserId = null): void;
}
