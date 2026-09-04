<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithLocalizedTitle;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithToast;
use App\Models\Partner;
use Livewire\Component;

class Partners extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public string $search = '';

    public bool $showEditModal = false;

    public ?string $editingPartnerId = null;

    public string $editName = '';
    public string $editEmail = '';
    public string $editTelegram = '';
    public string $editInstagram = '';
    public string $editTwitter = '';
    public string $editStatus = 'pending';
    public string $editBalance = '0';
    public string $editTotalEarned = '0';
    public ?string $editCreatedAt = null;

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

                    // PDF required fields (shown/edited in modal)
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

    public function openEdit(string $partnerId): void
    {
        $partner = Partner::query()->whereKey($partnerId)->first();

        if (! $partner) {
            return;
        }

        $social = $partner->social_contacts ?? [];

        $this->editingPartnerId = $partner->id;
        $this->editName = $partner->name;
        $this->editEmail = $partner->email;
        $this->editTelegram = (string) ($social['telegram'] ?? '');
        $this->editInstagram = (string) ($social['instagram'] ?? '');
        $this->editTwitter = (string) ($social['twitter'] ?? '');
        $this->editStatus = $partner->status ?: 'pending';
        $this->editBalance = (string) $partner->balance;
        $this->editTotalEarned = (string) $partner->total_earned;
        $this->editCreatedAt = $partner->created_at?->format('Y-m-d');

        $this->showEditModal = true;
    }

    public function closeEdit(): void
    {
        $this->showEditModal = false;
        $this->editingPartnerId = null;
    }

    public function saveEdit(): void
    {
        if (! $this->editingPartnerId) {
            return;
        }

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', 'max:255', 'unique:partners,email,'.$this->editingPartnerId.',id'],
            'editStatus' => ['required', 'in:active,pending,blocked'],
            'editTelegram' => ['nullable', 'string', 'max:255'],
            'editInstagram' => ['nullable', 'string', 'max:255'],
            'editTwitter' => ['nullable', 'string', 'max:255'],
            'editBalance' => ['required', 'numeric', 'min:0'],
            'editTotalEarned' => ['required', 'numeric', 'min:0'],
        ]);

        $partner = Partner::query()->whereKey($this->editingPartnerId)->first();
        if (! $partner) {
            return;
        }

        $partner->update([
            'name' => $this->editName,
            'email' => $this->editEmail,
            'status' => $this->editStatus,
            'balance' => (float) $this->editBalance,
            'total_earned' => (float) $this->editTotalEarned,
            'social_contacts' => [
                'telegram' => $this->editTelegram !== '' ? $this->editTelegram : null,
                'instagram' => $this->editInstagram !== '' ? $this->editInstagram : null,
                'twitter' => $this->editTwitter !== '' ? $this->editTwitter : null,
            ],
        ]);

        $this->closeEdit();
        $this->toast(__('admin.partners.updated_toast'));
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.partners', [
            'partners' => $this->filteredPartners(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.partners')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.partners');
    }
}
