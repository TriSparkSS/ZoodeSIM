<?php

namespace App\Services\Fraud\Contracts;

interface DeviceFraudServiceInterface
{
    public function assertCanRegister(?string $deviceId, ?string $ip, bool $applyingPromo): void;

    public function assertCanRegisterUserReferral(?string $deviceId, ?string $ip): void;

    public function assertDeviceCanRedeemPromo(string $deviceId, ?string $exceptUserId = null): void;

    public function assertDeviceCanRedeemUserReferral(string $deviceId, ?string $exceptUserId = null): void;
}
