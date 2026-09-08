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
            $created = $this->partners->createFromApplication($application);
            $partner = $created['partner'];

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
                'plain_password' => $created['plain_password'],
            ];
        });
    }

    public function reject(PartnerApplication $application): PartnerApplication
    {
        if ($application->status !== 'pending') {
            throw new RuntimeException('Only pending applications can be rejected.');
        }

        $application->update(['status' => 'rejected']);

        return $application->fresh();
    }

    public function suggestPromoCode(PartnerApplication $application): string
    {
        $name = trim($application->first_name.' '.($application->last_name ?? ''));

        return $this->promoCodes->suggestForName($name !== '' ? $name : $application->first_name);
    }
}
