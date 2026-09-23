<div>
    <x-ui.page-header
        :title="__('admin.partners.referrals_modal_title', ['name' => $subject->name])"
        :subtitle="$subject->email"
        :breadcrumbs="$breadcrumbs"
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('admin.partners')">
                {{ __('admin.partners.referrals_back') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card class="mb-6">
        <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.partners.referrals_total') }}</div>
        <div class="mt-1 text-2xl font-bold text-brand-green">${{ number_format($totalEarned, 2) }}</div>
    </x-ui.card>

    <x-ui.card>
        @if($referrals->isEmpty())
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('admin.partners.referrals_empty')"
                icon="🤝"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.date') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.referrals_table_user') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.referrals_table_code') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('admin.partners.referrals_table_earned') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($referrals as $referral)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="partner-referral-history-{{ $referral->id }}">
                                <td class="py-3.5 pe-4 align-middle text-surface-muted dark:text-brand-muted">
                                    {{ $referral->used_at?->format('Y-m-d H:i') }}
                                </td>
                                <td class="py-3.5 pe-4 align-middle">
                                    <div class="font-semibold">{{ $referral->user?->name ?? '—' }}</div>
                                    <div class="text-xs text-surface-muted dark:text-brand-muted">{{ $referral->user?->email }}</div>
                                </td>
                                <td class="py-3.5 pe-4 align-middle tracking-widest text-brand-cyan">
                                    {{ $referral->promoCode?->code ?? '—' }}
                                </td>
                                <td class="py-3.5 align-middle font-semibold text-brand-green">
                                    ${{ number_format((float) $referral->partner_reward, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $referrals->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
