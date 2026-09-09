<?php

namespace App\Services\Partner;

use App\DataTransferObjects\CreatePromoCodeData;
use App\Models\Partner;
use App\Models\PartnerApplication;
use App\Models\PromoCode;
use App\Services\Promo\PromoCodeService;
use App\Services\Referral\ReferralProgramSettings;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PartnerApplicationService
{
    public function __construct(
        protected PartnerService $partners,
        protected PromoCodeService $promoCodes,
        protected ReferralProgramSettings $program,
    ) {}

    /**
     * @return array{partner: Partner, promo: PromoCode, application: PartnerApplication, plain_password: string}
     */
    public function approve(PartnerApplication $application, string $promoCode): array
    {
        if ($application->status !== 'pending') {
            throw new RuntimeException('Only pending applications can be approved.');
        }

        $normalized = $this->promoCodes->normalizeCode($promoCode);
        $this->promoCodes->assertCodeIsAvailable($normalized);

        return DB::transaction(function () use ($application, $normalized) {
            $resolved = $this->resolvePartnerForApproval($application);
            $partner = $resolved['partner'];

            $promo = $this->promoCodes->create(new CreatePromoCodeData(
                partnerId: $partner->id,
                code: $normalized,
                bonusMb: $this->program->defaultUserBonusMb(),
                partnerReward: (float) $this->program->defaultRegistrationReward(),
                type: 'standard',
                expiresAt: now()->addDays(30),
                maxUsage: null,
                isActive: true,
            ));

            $application->update([
                'status' => 'approved',
                'partner_id' => $partner->id,
            ]);

            return [
                'partner' => $partner,
                'promo' => $promo,
                'application' => $application->fresh(),
                'plain_password' => $resolved['plain_password'],
            ];
        });
    }

    public function reject(PartnerApplication $application): PartnerApplication
    {
        if ($application->status !== 'pending') {
            throw new RuntimeException('Only pending applications can be rejected.');
        }

        return DB::transaction(function () use ($application) {
            $application->update(['status' => 'rejected']);

            $partner = $this->existingPartner($application);

            if ($partner !== null && $partner->isPending()) {
                $partner->update(['status' => 'blocked']);
            }

            return $application->fresh();
        });
    }

    public function suggestPromoCode(PartnerApplication $application): string
    {
        $name = trim($application->first_name.' '.($application->last_name ?? ''));

        return $this->promoCodes->suggestForName($name !== '' ? $name : $application->first_name);
    }

    /**
     * @return array{partner: Partner, plain_password: string}
     */
    protected function resolvePartnerForApproval(PartnerApplication $application): array
    {
        $existing = $this->existingPartner($application);

        if ($existing !== null) {
            return [
                'partner' => $this->partners->activateFromApplication($existing, $application),
                'plain_password' => '',
            ];
        }

        return $this->partners->createFromApplication($application);
    }

    protected function existingPartner(PartnerApplication $application): ?Partner
    {
        if ($application->partner_id) {
            $byId = Partner::query()->whereKey($application->partner_id)->first();

            if ($byId !== null) {
                return $byId;
            }
        }

        return Partner::query()->where('email', $application->email)->first();
    }
}
