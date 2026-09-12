<?php

namespace App\Livewire\Admin;

use App\DataTransferObjects\CreatePromoCodeData;
use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\Partner;
use App\Models\PromoCode;
use App\Services\Promo\PromoCodeService;
use App\Services\Referral\ReferralProgramSettings;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PromoCodes extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public string $search = '';

    public string $statusFilter = 'all';

    public bool $showCreateModal = false;

    public bool $showAssignModal = false;

    public string $formPartnerId = '';

    public string $formCode = '';

    public string $formBonusType = PromoCode::BONUS_TYPE_MB;

    public string $formBonusAmount = '200';

    public string $formPartnerReward = '1.50';

    public string $formType = 'standard';

    public string $formExpiresAt = '';

    public string $formMaxUsage = '';

    public string $formUnlockRequirement = '';

    public bool $formDeactivateExisting = true;

    protected PromoCodeService $promoCodeService;

    protected ReferralProgramSettings $program;

    public function boot(PromoCodeService $promoCodeService, ReferralProgramSettings $program): void
    {
        $this->promoCodeService = $promoCodeService;
        $this->program = $program;
    }

    public function openCreateModal(?string $partnerId = null): void
    {
        $this->resetForm();
        $this->formPartnerId = $partnerId ?? '';
        $this->formExpiresAt = now()->addDays(30)->format('Y-m-d');
        $this->formDeactivateExisting = false;
        $this->showAssignModal = false;
        $this->showCreateModal = true;

        if ($this->formPartnerId !== '') {
            $this->suggestCodeForSelectedPartner();
        }
    }

    public function openAssignModal(?string $partnerId = null): void
    {
        $this->resetForm();
        $this->formPartnerId = $partnerId ?? '';
        $this->formExpiresAt = now()->addDays(30)->format('Y-m-d');
        $this->formDeactivateExisting = true;
        $this->showCreateModal = false;
        $this->showAssignModal = true;

        if ($this->formPartnerId !== '') {
            $this->suggestCodeForSelectedPartner();
        }
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showAssignModal = false;
        $this->resetForm();
    }

    public function updatedFormPartnerId(): void
    {
        if ($this->formPartnerId !== '' && $this->formCode === '') {
            $this->suggestCodeForSelectedPartner();
        }
    }

    public function setBonusType(string $type): void
    {
        $this->formBonusType = $type === PromoCode::BONUS_TYPE_USD
            ? PromoCode::BONUS_TYPE_USD
            : PromoCode::BONUS_TYPE_MB;
        $this->updatedFormBonusType();
    }

    public function updatedFormBonusType(): void
    {
        $this->resetValidation('formBonusAmount');
        $this->formBonusAmount = $this->formBonusType === PromoCode::BONUS_TYPE_USD
            ? '1.00'
            : (string) $this->program->defaultUserBonusMb();
    }

    public function generateCode(): void
    {
        $this->suggestCodeForSelectedPartner();
    }

    public function createPromo(): void
    {
        $this->persistPromo(deactivateExisting: $this->formDeactivateExisting, assignMode: false);
    }

    public function assignPromo(): void
    {
        $this->persistPromo(deactivateExisting: true, assignMode: true);
    }

    public function deactivate(string $id): void
    {
        $promo = PromoCode::query()->whereKey($id)->first();

        if (! $promo) {
            return;
        }

        $this->promoCodeService->deactivate($promo);
        $this->toast(__('admin.promo_codes.deactivated_toast', ['code' => $promo->code]));
    }

    public function activate(string $id): void
    {
        $promo = PromoCode::query()->whereKey($id)->first();

        if (! $promo) {
            return;
        }

        try {
            $this->promoCodeService->activate($promo);
        } catch (ValidationException $e) {
            $this->toast(collect($e->errors())->flatten()->first() ?: __('admin.promo_codes.validation.locked_activate'), 'error');

            return;
        }

        $this->toast(__('admin.promo_codes.activated_toast', ['code' => $promo->code]));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function promoCodes(): array
    {
        return PromoCode::query()
            ->with('partner')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PromoCode $promo) => [
                'id' => $promo->id,
                'partner_id' => $promo->partner_id,
                'partner' => $promo->partner?->name ?? '—',
                'code' => $promo->code,
                'uses' => (int) $promo->usage_count,
                'max_usage' => $promo->max_usage,
                'uses_label' => $promo->max_usage === null
                    ? number_format((int) $promo->usage_count)
                    : number_format((int) $promo->usage_count).' / '.number_format((int) $promo->max_usage),
                'bonus' => $promo->userBonusLabel(),
                'reward' => '$'.number_format((float) $promo->partner_reward, 2),
                'type' => $promo->type,
                'expires_at' => $promo->expires_at?->format('Y-m-d'),
                'is_active' => $promo->is_active,
                'is_locked' => $promo->isLocked(),
                'unlock_requirement' => $promo->unlock_requirement,
                'status' => $promo->lifecycleStatus(),
                'needs_replacement' => ! $promo->isCurrentlyUsable() && ! $promo->isLocked(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function filteredPromoCodes(): array
    {
        $promoCodes = $this->promoCodes();

        if ($this->statusFilter !== 'all') {
            $promoCodes = array_values(array_filter(
                $promoCodes,
                fn (array $promo) => $promo['status'] === $this->statusFilter
            ));
        }

        if ($this->search === '') {
            return $promoCodes;
        }

        $query = mb_strtolower($this->search);

        return array_values(array_filter(
            $promoCodes,
            fn (array $promo) => str_contains(mb_strtolower((string) $promo['partner']), $query)
                || str_contains(mb_strtolower($promo['code']), $query)
        ));
    }

    /**
     * @return array<int, array{id: string, name: string, needs_replacement: bool}>
     */
    public function partnerOptions(): array
    {
        return Partner::query()
            ->with('promoCodes')
            ->orderBy('name')
            ->get()
            ->map(function (Partner $partner) {
                $hasUsable = $partner->promoCodes->contains(
                    fn (PromoCode $promo) => $promo->isCurrentlyUsable()
                );

                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'needs_replacement' => ! $hasUsable,
                ];
            })
            ->all();
    }

    protected function persistPromo(bool $deactivateExisting, bool $assignMode): void
    {
        $validated = $this->validate([
            'formPartnerId' => ['required', 'uuid', 'exists:partners,id'],
            'formCode' => ['required', 'string', 'min:6', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
            'formBonusType' => ['required', 'in:'.implode(',', PromoCode::bonusTypes())],
            'formBonusAmount' => $this->formBonusType === PromoCode::BONUS_TYPE_USD
                ? ['required', 'numeric', 'min:0.01', 'max:100000']
                : ['required', 'integer', 'min:1', 'max:100000'],
            'formPartnerReward' => ['required', 'numeric', 'min:0'],
            'formType' => ['required', 'in:standard,premium,seasonal,single'],
            'formExpiresAt' => ['nullable', 'date'],
            'formMaxUsage' => ['nullable', 'integer', 'min:1'],
            'formUnlockRequirement' => ['nullable', 'integer', 'min:1'],
        ], [
            'formPartnerId.required' => __('admin.promo_codes.validation.partner_required'),
            'formCode.required' => __('admin.promo_codes.validation.code_required'),
            'formCode.min' => __('admin.promo_codes.validation.code_length'),
            'formCode.max' => __('admin.promo_codes.validation.code_length'),
            'formCode.regex' => __('admin.promo_codes.validation.code_format'),
        ]);

        $partner = Partner::query()->whereKey($validated['formPartnerId'])->first();

        if (! $partner) {
            return;
        }

        try {
            $isUsd = $validated['formBonusType'] === PromoCode::BONUS_TYPE_USD;
            $bonusAmount = (float) $validated['formBonusAmount'];
            $unlockRequirement = filled($validated['formUnlockRequirement'] ?? null)
                ? (int) $validated['formUnlockRequirement']
                : null;
            $queued = $unlockRequirement !== null && $unlockRequirement >= 1;

            $promo = $this->promoCodeService->create(new CreatePromoCodeData(
                partnerId: $partner->id,
                code: $validated['formCode'],
                bonusMb: $isUsd ? 0 : (int) $validated['formBonusAmount'],
                partnerReward: (float) $validated['formPartnerReward'],
                type: $validated['formType'],
                expiresAt: filled($validated['formExpiresAt'] ?? null)
                    ? Carbon::parse($validated['formExpiresAt'])->endOfDay()
                    : null,
                maxUsage: filled($validated['formMaxUsage'] ?? null)
                    ? (int) $validated['formMaxUsage']
                    : null,
                isActive: ! $queued,
                deactivateExistingActive: $queued ? false : $deactivateExisting,
                bonusType: $validated['formBonusType'],
                bonusAmount: $bonusAmount,
                unlockRequirement: $unlockRequirement,
            ));
        } catch (ValidationException $e) {
            if (isset($e->errors()['unlock_requirement'])) {
                $this->addError('formUnlockRequirement', $e->errors()['unlock_requirement'][0]);
            }

            $this->toast(collect($e->errors())->flatten()->first() ?: __('admin.promo_codes.validation.code_unique'), 'error');

            return;
        }

        $this->closeModals();

        $toastKey = $assignMode
            ? 'admin.promo_codes.assigned_toast'
            : 'admin.promo_codes.created_toast';

        $this->toast(__($toastKey, [
            'code' => $promo->code,
            'partner' => $partner->name,
        ]));
    }

    protected function suggestCodeForSelectedPartner(): void
    {
        if ($this->formPartnerId === '') {
            return;
        }

        $partner = Partner::query()->whereKey($this->formPartnerId)->first();

        if (! $partner) {
            return;
        }

        $this->formCode = $this->promoCodeService->suggestForName($partner->name);
    }

    protected function resetForm(): void
    {
        $this->resetValidation();
        $this->formPartnerId = '';
        $this->formCode = '';
        $this->applySettingDefaults();
        $this->formType = 'standard';
        $this->formExpiresAt = '';
        $this->formMaxUsage = '';
        $this->formUnlockRequirement = '';
        $this->formDeactivateExisting = true;
    }

    protected function applySettingDefaults(): void
    {
        $this->formBonusType = PromoCode::BONUS_TYPE_MB;
        $this->formBonusAmount = (string) $this->program->defaultUserBonusMb();
        $this->formPartnerReward = $this->program->defaultRegistrationReward();
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.promo-codes', [
            'promoCodes' => $this->filteredPromoCodes(),
            'partnerOptions' => $this->partnerOptions(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.promo_codes')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.promo_codes');
    }
}
