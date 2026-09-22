<?php

namespace App\Services\Referral;

use App\DataTransferObjects\PromoValidationResult;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\Notifications\Contracts\NotificationDispatcherInterface;
use App\Services\Referral\Contracts\UserReferralServiceInterface;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

class UserReferralService implements UserReferralServiceInterface
{
    public function __construct(
        protected UserReferralCodeGenerator $codes,
        protected ReferralProgramSettings $program,
        protected WalletLedgerServiceInterface $ledger,
        protected NotificationDispatcherInterface $notifications,
    ) {}

    public function validate(string $code, ?string $email = null): PromoValidationResult
    {
        try {
            $referrer = $this->findReferrer($code);
            $this->assertNotSelfReferral($referrer, $email);
        } catch (ValidationException $e) {
            $reason = collect($e->errors())->flatten()->first();

            return PromoValidationResult::invalid(is_string($reason) ? $reason : __('api.promo.invalid'));
        }

        $this->assertNotSelfReferral($referrer, $email);

        return PromoValidationResult::validUserReferral(
            (float) $this->program->userReferralInviteeReward(),
        );
    }

    public function findReferrer(string $code): User
    {
        try {
            $normalized = $this->codes->normalize($code);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'referral_code' => __('api.promo.invalid'),
            ]);
        }

        $referrer = User::query()->where('referral_code', $normalized)->first();

        if ($referrer === null) {
            throw ValidationException::withMessages([
                'referral_code' => __('api.promo.invalid'),
            ]);
        }

        return $referrer;
    }

    public function redeem(User $invitee, User $referrer): UserReferral
    {
        if ($invitee->id === $referrer->id) {
            throw ValidationException::withMessages([
                'referral_code' => __('api.promo.self_referral'),
            ]);
        }

        $this->assertNotSelfReferral($referrer, $invitee->email);

        $currency = (string) config('pricing.currency', 'USD');
        $referrerAmount = Money::fromDecimal($this->program->userReferralReferrerReward(), $currency);
        $inviteeAmount = Money::fromDecimal($this->program->userReferralInviteeReward(), $currency);

        $invitee->forceFill([
            'referred_by_user_id' => $referrer->id,
        ])->save();

        $record = UserReferral::query()->create([
            'referrer_id' => $referrer->id,
            'referred_id' => $invitee->id,
            'referrer_amount' => $referrerAmount->toDecimal(),
            'referred_amount' => $inviteeAmount->toDecimal(),
            'currency' => $currency,
        ]);

        if ($inviteeAmount->cents > 0) {
            $this->ledger->credit(
                $invitee,
                $inviteeAmount,
                Transaction::CATEGORY_USER_REFERRAL_BONUS,
                'user_referral',
                $record->id,
                'User referral bonus',
                meta: [
                    'referrer_id' => $referrer->id,
                    'referral_code' => $referrer->referral_code,
                ],
            );
        }

        if ($referrerAmount->cents > 0) {
            $this->ledger->credit(
                $referrer,
                $referrerAmount,
                Transaction::CATEGORY_USER_REFERRAL_REWARD,
                'user_referral',
                $record->id,
                'User referral reward',
                meta: [
                    'referred_id' => $invitee->id,
                    'referral_code' => $referrer->referral_code,
                ],
            );
        }

        $this->notifications->userReferralCredited(
            $referrer->fresh() ?? $referrer,
            $invitee->fresh() ?? $invitee,
            $record,
        );

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $records = $user->referralRecords()
            ->with('referred:id,name,created_at')
            ->orderByDesc('created_at')
            ->get();

        return [
            'referral_code' => $user->referral_code,
            'referrer_reward' => (float) $this->program->userReferralReferrerReward(),
            'referred_reward' => (float) $this->program->userReferralInviteeReward(),
            'referral_count' => $records->count(),
            'total_earned' => $records->sum(fn (UserReferral $row) => (float) $row->referrer_amount),
            'referrals' => $records->map(fn (UserReferral $row) => [
                'id' => $row->referred_id,
                'name' => $row->referred?->name,
                'created_at' => $row->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    public function assertNotSelfReferral(User $referrer, ?string $email): void
    {
        if ($email !== null && $email !== '' && strcasecmp($email, $referrer->email) === 0) {
            throw ValidationException::withMessages([
                'referral_code' => __('api.promo.self_referral'),
            ]);
        }
    }
}
