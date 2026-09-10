<?php

namespace App\Livewire\Admin;

use App\DataTransferObjects\AdjustUserBalanceData;
use App\DataTransferObjects\UpdateUserData;
use App\Livewire\Concerns\ResolvesAuthenticatedAdmin;
use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\Transaction;
use App\Models\User;
use App\Services\User\Contracts\UserAdminServiceInterface;
use App\Services\User\Contracts\UserBalanceAdjustmentServiceInterface;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Users extends Component
{
    use ResolvesAuthenticatedAdmin;
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public string $search = '';

    public bool $showEditModal = false;

    public bool $showWalletModal = false;

    public ?string $editingUserId = null;

    public string $actingUserName = '';

    public string $editName = '';

    public string $editEmail = '';

    public string $editPhone = '';

    public string $editPassword = '';

    public string $editPasswordConfirmation = '';

    public ?string $editCreatedAt = null;

    public int $editBonusMb = 0;

    public string $walletBalance = '0';

    public int $walletBonusMb = 0;

    public string $walletDirection = 'credit';

    public string $walletAmount = '';

    public string $walletNote = '';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function filteredUsers(): array
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'bonus_mb' => (int) $user->bonus_mb,
                'balance' => (string) $user->balance,
                'created_at' => $user->created_at?->format('Y-m-d'),
            ])
            ->all();

        if ($this->search === '') {
            return $users;
        }

        $query = mb_strtolower($this->search);

        return array_values(array_filter(
            $users,
            fn (array $user) => str_contains(mb_strtolower((string) $user['name']), $query)
                || str_contains(mb_strtolower((string) $user['email']), $query)
                || str_contains(mb_strtolower((string) $user['phone']), $query)
        ));
    }

    public function openEdit(string $userId): void
    {
        $user = User::query()->whereKey($userId)->first();

        if (! $user) {
            return;
        }

        $this->closeWallet();
        $this->resetValidation();
        $this->editingUserId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editPhone = (string) $user->phone;
        $this->editPassword = '';
        $this->editPasswordConfirmation = '';
        $this->editCreatedAt = $user->created_at?->format('Y-m-d');
        $this->editBonusMb = (int) $user->bonus_mb;
        $this->showEditModal = true;
    }

    public function closeEdit(): void
    {
        $this->showEditModal = false;
        $this->editingUserId = null;
        $this->editPassword = '';
        $this->editPasswordConfirmation = '';
        $this->resetValidation();
    }

    public function saveEdit(UserAdminServiceInterface $users): void
    {
        if (! $this->editingUserId) {
            return;
        }

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->editingUserId.',id'],
            'editPhone' => ['required', 'string', 'max:30', 'unique:users,phone,'.$this->editingUserId.',id'],
            'editPassword' => ['nullable', 'string', 'min:8', 'same:editPasswordConfirmation'],
            'editPasswordConfirmation' => ['nullable', 'string'],
        ], [
            'editName.required' => __('admin.users.validation.name_required'),
            'editEmail.required' => __('admin.users.validation.email_required'),
            'editEmail.email' => __('admin.users.validation.email_invalid'),
            'editEmail.unique' => __('admin.users.validation.email_unique'),
            'editPhone.required' => __('admin.users.validation.phone_required'),
            'editPhone.unique' => __('admin.users.validation.phone_unique'),
            'editPassword.min' => __('admin.users.validation.password_min'),
            'editPassword.same' => __('admin.users.validation.password_confirmed'),
        ]);

        $user = User::query()->whereKey($this->editingUserId)->first();

        if (! $user) {
            return;
        }

        $users->update($user, $this->admin(), new UpdateUserData(
            name: $validated['editName'],
            email: $validated['editEmail'],
            phone: $validated['editPhone'],
            password: filled($validated['editPassword'] ?? null) ? $validated['editPassword'] : null,
        ));

        $this->closeEdit();
        $this->toast(__('admin.users.updated_toast'));
    }

    public function openWallet(string $userId): void
    {
        $user = User::query()->whereKey($userId)->first();

        if (! $user) {
            return;
        }

        $this->closeEdit();
        $this->resetValidation();
        $this->editingUserId = $user->id;
        $this->actingUserName = $user->name;
        $this->syncWalletDisplay($user);
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

    public function adjustWallet(UserBalanceAdjustmentServiceInterface $adjustments): void
    {
        if (! $this->editingUserId) {
            return;
        }

        $this->validate([
            'walletDirection' => ['required', 'in:credit,debit'],
            'walletAmount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'walletNote' => ['required', 'string', 'max:255'],
        ], [
            'walletDirection.in' => __('admin.users.wallet.validation.direction_invalid'),
            'walletAmount.required' => __('admin.users.wallet.validation.amount_required'),
            'walletAmount.regex' => __('admin.users.wallet.validation.amount_format'),
            'walletNote.required' => __('admin.users.wallet.validation.note_required'),
        ]);

        $user = User::query()->whereKey($this->editingUserId)->first();

        if (! $user) {
            return;
        }

        try {
            $transaction = $adjustments->adjustByAdmin($user, $this->admin(), new AdjustUserBalanceData(
                direction: $this->walletDirection,
                amount: $this->walletAmount,
                note: $this->walletNote,
            ));
        } catch (ValidationException $e) {
            $this->toast(collect($e->errors())->flatten()->first() ?: __('admin.users.wallet.failed'), 'error');

            return;
        }

        $this->syncWalletDisplay($user->fresh());
        $this->walletAmount = '';
        $this->walletNote = '';
        $this->resetValidation();

        $this->toast(__('admin.users.wallet.adjusted_toast', [
            'type' => $transaction->type === Transaction::TYPE_CREDIT
                ? __('admin.users.wallet.credit')
                : __('admin.users.wallet.debit'),
            'amount' => '$'.number_format((float) $transaction->amount, 2),
            'balance' => '$'.number_format((float) $transaction->balance_after, 2),
        ]));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function walletTransactions(): array
    {
        if (! $this->showWalletModal || ! $this->editingUserId) {
            return [];
        }

        return Transaction::query()
            ->where('transactable_type', (new User)->getMorphClass())
            ->where('transactable_id', $this->editingUserId)
            ->where('currency', '!=', 'MB')
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

    protected function syncWalletDisplay(?User $user): void
    {
        if ($user === null) {
            return;
        }

        $this->walletBalance = (string) $user->balance;
        $this->walletBonusMb = (int) $user->bonus_mb;
        $this->actingUserName = $user->name;
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.users', [
            'users' => $this->filteredUsers(),
            'walletTransactions' => $this->walletTransactions(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.users')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.users');
    }
}
