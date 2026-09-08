<?php

namespace App\Livewire\Admin;

use App\DataTransferObjects\AdjustPartnerBalanceData;
use App\Livewire\Concerns\ResolvesAuthenticatedAdmin;
use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\Partner;
use App\Models\Transaction;
use App\Services\Partner\Contracts\PartnerBalanceAdjustmentServiceInterface;
use App\Services\Partner\PartnerService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Partners extends Component
{
    use ResolvesAuthenticatedAdmin;
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public string $search = '';

    public bool $showProfileModal = false;

    public bool $showPasswordModal = false;

    public bool $showWalletModal = false;

    public ?string $actingPartnerId = null;

    public string $actingPartnerName = '';

    public string $editName = '';

    public string $editEmail = '';

    public string $editTelegram = '';

    public string $editInstagram = '';

    public string $editTwitter = '';

    public string $editStatus = 'pending';

    public ?string $editCreatedAt = null;

    public string $editPassword = '';

    public string $editPasswordConfirmation = '';

    public bool $editRevokeSessions = true;

    public string $walletBalance = '0';

    public string $walletTotalEarned = '0';

    public string $walletDirection = 'credit';

    public string $walletAmount = '';

    public string $walletNote = '';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function filteredPartners(): array
    {
        $partners = Partner::query()
            ->with('promoCodes')
            ->get()
            ->map(function (Partner $partner) {
                $activePromo = $partner->promoCodes
                    ->sortByDesc('created_at')
                    ->first(fn ($promo) => $promo->isCurrentlyUsable())
                    ?? $partner->promoCodes
                        ->sortByDesc('created_at')
                        ->first(fn ($promo) => $promo->is_active);

                $registrations = (int) $partner->promoCodes->sum('usage_count');

                // Demo bucket: keep the old "level" visual meaning, without implementing referrals yet.
                $level = $registrations >= 100 || $partner->total_earned >= 500 ? 2 : 1;

                $social = $partner->social_contacts ?? [];

                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'level' => $level,
                    'registrations' => $registrations,
                    'earnings' => (float) $partner->total_earned,
                    'promo' => $activePromo?->code,
                    'status' => $partner->status,
                    'social_contacts' => $social,
                    'balance' => (float) $partner->balance,
                    'total_earned' => (float) $partner->total_earned,
                    'created_at' => $partner->created_at?->format('Y-m-d'),
                ];
            })
            ->all();

        if ($this->search === '') {
            return $partners;
        }

        $query = mb_strtolower($this->search);

        return array_values(array_filter(
            $partners,
            fn (array $partner) => str_contains(mb_strtolower($partner['name']), $query)
                || str_contains(mb_strtolower($partner['email']), $query)
                || ($partner['promo'] && str_contains(mb_strtolower($partner['promo']), $query))
        ));
    }

    public function openProfile(string $partnerId): void
    {
        $partner = $this->findPartner($partnerId);

        if ($partner === null) {
            return;
        }

        $this->closeAllModals();
        $this->fillActingPartner($partner);

        $social = $partner->social_contacts ?? [];
        $this->editName = $partner->name;
        $this->editEmail = $partner->email;
        $this->editTelegram = (string) ($social['telegram'] ?? '');
        $this->editInstagram = (string) ($social['instagram'] ?? '');
        $this->editTwitter = (string) ($social['twitter'] ?? '');
        $this->editStatus = $partner->status ?: 'pending';
        $this->editCreatedAt = $partner->created_at?->format('Y-m-d');
        $this->showProfileModal = true;
    }

    public function closeProfile(): void
    {
        $this->showProfileModal = false;
        $this->resetValidation();
    }

    public function saveProfile(): void
    {
        if (! $this->actingPartnerId) {
            return;
        }

        $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', 'max:255', 'unique:partners,email,'.$this->actingPartnerId.',id'],
            'editStatus' => ['required', 'in:active,pending,blocked'],
            'editTelegram' => ['nullable', 'string', 'max:255'],
            'editInstagram' => ['nullable', 'string', 'max:255'],
            'editTwitter' => ['nullable', 'string', 'max:255'],
        ]);

        $partner = $this->findPartner($this->actingPartnerId);

        if ($partner === null) {
            return;
        }

        $partner->update([
            'name' => $this->editName,
            'email' => $this->editEmail,
            'status' => $this->editStatus,
            'social_contacts' => [
                'telegram' => $this->editTelegram !== '' ? $this->editTelegram : null,
                'instagram' => $this->editInstagram !== '' ? $this->editInstagram : null,
                'twitter' => $this->editTwitter !== '' ? $this->editTwitter : null,
            ],
        ]);

        $this->closeAllModals();
        $this->toast(__('admin.partners.updated_toast'));
    }

    public function openPassword(string $partnerId): void
    {
        $partner = $this->findPartner($partnerId);

        if ($partner === null) {
            return;
        }

        $this->closeAllModals();
        $this->fillActingPartner($partner);
        $this->editPassword = '';
        $this->editPasswordConfirmation = '';
        $this->editRevokeSessions = true;
        $this->showPasswordModal = true;
    }

    public function closePassword(): void
    {
        $this->showPasswordModal = false;
        $this->editPassword = '';
        $this->editPasswordConfirmation = '';
        $this->resetValidation();
    }

    public function savePassword(PartnerService $partners): void
    {
        if (! $this->actingPartnerId) {
            return;
        }

        $this->validate([
            'editPassword' => ['required', 'string', 'min:8', 'same:editPasswordConfirmation'],
            'editPasswordConfirmation' => ['required', 'string'],
            'editRevokeSessions' => ['boolean'],
        ], [
            'editPassword.required' => __('admin.partners.validation.password_required'),
            'editPassword.min' => __('admin.partners.validation.password_min'),
            'editPassword.same' => __('admin.partners.validation.password_confirmed'),
            'editPasswordConfirmation.required' => __('admin.partners.validation.password_confirmed'),
        ]);

        $partner = $this->findPartner($this->actingPartnerId);

        if ($partner === null) {
            return;
        }

        $partners->updatePassword(
            $partner,
            $this->editPassword,
            $this->editRevokeSessions,
        );

        $this->closeAllModals();
        $this->toast(__('admin.partners.password_updated_toast'));
    }

    public function openWallet(string $partnerId): void
    {
        $partner = $this->findPartner($partnerId);

        if ($partner === null) {
            return;
        }

        $this->closeAllModals();
        $this->fillActingPartner($partner);
        $this->syncWalletDisplay($partner);
        $this->walletDirection = 'credit';
        $this->walletAmount = '';
        $this->walletNote = '';
        $this->showWalletModal = true;
    }

    public function closeWallet(): void
    {
        $this->showWalletModal = false;
        $this->walletAmount = '';
        $this->walletNote = '';
        $this->resetValidation();
    }

    public function adjustWallet(PartnerBalanceAdjustmentServiceInterface $adjustments): void
    {
        if (! $this->actingPartnerId) {
            return;
        }

        $this->validate([
            'walletDirection' => ['required', 'in:credit,debit'],
            'walletAmount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'walletNote' => ['required', 'string', 'max:255'],
        ], [
            'walletDirection.in' => __('admin.partners.wallet.validation.direction_invalid'),
            'walletAmount.required' => __('admin.partners.wallet.validation.amount_required'),
            'walletAmount.regex' => __('admin.partners.wallet.validation.amount_format'),
            'walletNote.required' => __('admin.partners.wallet.validation.note_required'),
        ]);

        $partner = $this->findPartner($this->actingPartnerId);

        if ($partner === null) {
            return;
        }

        try {
            $transaction = $adjustments->adjust($partner, $this->admin(), new AdjustPartnerBalanceData(
                direction: $this->walletDirection,
                amount: $this->walletAmount,
                note: $this->walletNote,
            ));
        } catch (ValidationException $e) {
            $this->toast(collect($e->errors())->flatten()->first() ?: __('admin.partners.wallet.failed'), 'error');

            return;
        }

        $this->syncWalletDisplay($partner->fresh());
        $this->walletAmount = '';
        $this->walletNote = '';
        $this->resetValidation();

        $this->toast(__('admin.partners.wallet.adjusted_toast', [
            'type' => $transaction->type === Transaction::TYPE_CREDIT
                ? __('admin.partners.wallet.credit')
                : __('admin.partners.wallet.debit'),
            'amount' => '$'.number_format((float) $transaction->amount, 2),
            'balance' => '$'.number_format((float) $transaction->balance_after, 2),
        ]));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function walletTransactions(): array
    {
        if (! $this->showWalletModal || ! $this->actingPartnerId) {
            return [];
        }

        return Transaction::query()
            ->where('transactable_type', (new Partner)->getMorphClass())
            ->where('transactable_id', $this->actingPartnerId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'category' => $transaction->category,
                'amount' => (float) $transaction->amount,
                'balance_after' => (float) $transaction->balance_after,
                'description' => $transaction->description,
                'date' => $transaction->created_at?->format('Y-m-d H:i'),
            ])
            ->all();
    }

    protected function syncWalletDisplay(?Partner $partner): void
    {
        if ($partner === null) {
            return;
        }

        $this->walletBalance = (string) $partner->balance;
        $this->walletTotalEarned = (string) $partner->total_earned;
    }

    protected function findPartner(string $partnerId): ?Partner
    {
        return Partner::query()->whereKey($partnerId)->first();
    }

    protected function fillActingPartner(Partner $partner): void
    {
        $this->actingPartnerId = $partner->id;
        $this->actingPartnerName = $partner->name;
        $this->resetValidation();
    }

    protected function closeAllModals(): void
    {
        $this->showProfileModal = false;
        $this->showPasswordModal = false;
        $this->showWalletModal = false;
        $this->editPassword = '';
        $this->editPasswordConfirmation = '';
        $this->walletAmount = '';
        $this->walletNote = '';
        $this->resetValidation();
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.partners', [
            'partners' => $this->filteredPartners(),
            'walletTransactions' => $this->walletTransactions(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.partners')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.partners');
    }
}
