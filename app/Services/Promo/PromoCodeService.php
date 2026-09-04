<?php

namespace App\Services\Promo;

use App\DataTransferObjects\CreatePromoCodeData;
use App\Models\Partner;
use App\Models\PromoCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromoCodeService
{
    public function __construct(
        protected PromoCodeGenerator $generator,
    ) {}

    public function normalizeCode(string $code): string
    {
        return $this->generator->normalize($code);
    }

    public function assertCodeIsAvailable(string $code, ?string $ignoreId = null): void
    {
        $normalized = $this->normalizeCode($code);

        $query = PromoCode::query()->where('code', $normalized);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'code' => __('admin.promo_codes.validation.code_unique'),
            ]);
        }
    }

    public function create(CreatePromoCodeData $data): PromoCode
    {
        $code = $this->normalizeCode($data->code);
        $this->assertCodeIsAvailable($code);

        return DB::transaction(function () use ($data, $code) {
            if ($data->deactivateExistingActive) {
                $this->deactivateActiveForPartner($data->partnerId);
            }

            return PromoCode::query()->create([
                'partner_id' => $data->partnerId,
                'code' => $code,
                'bonus_mb' => $data->bonusMb,
                'partner_reward' => $data->partnerReward,
                'type' => $data->type,
                'expires_at' => $data->expiresAt,
                'is_active' => $data->isActive,
                'usage_count' => 0,
                'max_usage' => $data->maxUsage,
            ]);
        });
    }

    /**
     * Assign a replacement promo code to a partner (e.g. after expiry or usage limit).
     */
    public function assignToPartner(
        Partner $partner,
        string $code,
        int $bonusMb = 200,
        float $partnerReward = 1.50,
        string $type = 'standard',
        ?\Carbon\CarbonInterface $expiresAt = null,
        ?int $maxUsage = null,
        bool $deactivateExistingActive = true,
    ): PromoCode {
        return $this->create(new CreatePromoCodeData(
            partnerId: $partner->id,
            code: $code,
            bonusMb: $bonusMb,
            partnerReward: $partnerReward,
            type: $type,
            expiresAt: $expiresAt ?? now()->addDays(30),
            maxUsage: $maxUsage,
            isActive: true,
            deactivateExistingActive: $deactivateExistingActive,
        ));
    }

    public function deactivate(PromoCode $promoCode): PromoCode
    {
        $promoCode->update(['is_active' => false]);

        return $promoCode->refresh();
    }

    public function activate(PromoCode $promoCode): PromoCode
    {
        $promoCode->update(['is_active' => true]);

        return $promoCode->refresh();
    }

    public function deactivateActiveForPartner(string $partnerId): void
    {
        PromoCode::query()
            ->where('partner_id', $partnerId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    public function suggestForName(string $name): string
    {
        return $this->generator->suggestFromName($name);
    }
}
