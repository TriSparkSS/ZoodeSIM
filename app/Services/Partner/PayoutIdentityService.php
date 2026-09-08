<?php

namespace App\Services\Partner;

use App\Models\Partner;
use App\Models\PromoUsage;
use App\Models\User;
use App\Services\Partner\Contracts\PayoutIdentityServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PayoutIdentityService implements PayoutIdentityServiceInterface
{
    public function assertReferralIsNotPartner(Partner $partner, ?string $payoutDetails = null): void
    {
        $referrals = $this->referredUsers($partner);

        if ($referrals->isEmpty()) {
            return;
        }

        $partnerEmail = $this->normalize($partner->email);
        $details = $this->normalize((string) $payoutDetails);
        $detailsDigits = $this->digits((string) $payoutDetails);
        $shadow = $this->userByEmail($partner->email);

        foreach ($referrals as $user) {
            $email = $this->normalize((string) $user->email);
            $phone = $this->normalize((string) $user->phone);
            $phoneDigits = $this->digits((string) $user->phone);

            if ($partnerEmail !== '' && $email === $partnerEmail) {
                $this->reject();
            }

            if ($details !== '' && ($details === $email || $details === $phone)) {
                $this->reject();
            }

            if ($detailsDigits !== '' && strlen($detailsDigits) >= 8 && $detailsDigits === $phoneDigits) {
                $this->reject();
            }

            if ($shadow !== null && $shadow->id !== $user->id) {
                if ($this->sameDevice($shadow, $user) || $this->samePhone($shadow, $user)) {
                    $this->reject();
                }
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    protected function referredUsers(Partner $partner): Collection
    {
        $userIds = PromoUsage::query()
            ->where('partner_id', $partner->id)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $userIds)->get();
    }

    protected function userByEmail(string $email): ?User
    {
        $normalized = $this->normalize($email);

        if ($normalized === '') {
            return null;
        }

        return User::query()
            ->whereRaw('lower(email) = ?', [$normalized])
            ->first();
    }

    protected function sameDevice(User $left, User $right): bool
    {
        $a = trim((string) $left->device_id);
        $b = trim((string) $right->device_id);

        return $a !== '' && $a === $b;
    }

    protected function samePhone(User $left, User $right): bool
    {
        $a = $this->digits((string) $left->phone);
        $b = $this->digits((string) $right->phone);

        return $a !== '' && strlen($a) >= 8 && $a === $b;
    }

    protected function normalize(string $value): string
    {
        return strtolower(preg_replace('/\s+/', '', trim($value)) ?? '');
    }

    protected function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    protected function reject(): never
    {
        throw ValidationException::withMessages([
            'amount' => [__('partner.earnings.validation.identity_mismatch')],
            'withdrawal' => [__('admin.payouts.validation.identity_mismatch')],
        ]);
    }
}
