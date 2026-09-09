@php
    $platformIcons = ['instagram' => '📸', 'telegram' => '✈️', 'tiktok' => '🎵', 'youtube' => '▶️'];
    $filterTabs = [
        'all' => __('ui.all'),
        'pending' => __('admin.applications.filter_pending'),
        'approved' => __('admin.applications.filter_approved'),
        'rejected' => __('admin.applications.filter_rejected'),
    ];
@endphp

<div>
    <x-ui.page-header
        :title="__('admin.applications.title')"
        :subtitle="__('admin.applications.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    {{-- Stats Row --}}
    <div class="mb-7 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.stat-card
            :label="__('admin.applications.pending')"
            :value="(string) $stats['pending']"
            color="yellow"
        />
        <x-ui.stat-card
            :label="__('admin.applications.approved')"
            :value="(string) $stats['approved']"
            color="green"
        />
        <x-ui.stat-card
            :label="__('admin.applications.rejected')"
            :value="(string) $stats['rejected']"
            color="red"
        />
        <x-ui.stat-card
            :label="__('admin.applications.total')"
            :value="(string) $stats['total']"
            color="cyan"
        />
    </div>

    {{-- Applications Table --}}
    <x-ui.card>
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-base font-bold text-surface-text dark:text-brand-text">{{ __('admin.applications.list_title') }}</h3>

            <div class="flex flex-wrap gap-1.5">
                @foreach($filterTabs as $key => $label)
                    <label @class([
                        'cursor-pointer rounded-lg border px-3.5 py-1.5 text-xs font-semibold transition-all duration-200',
                        'border-brand-cyan bg-brand-cyan/10 text-brand-cyan' => $filter === $key,
                        'border-surface-border text-surface-muted hover:text-surface-text dark:border-brand-border dark:text-brand-muted dark:hover:text-brand-text' => $filter !== $key,
                    ])>
                        <input type="radio" wire:model.live="filter" value="{{ $key }}" class="sr-only">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        @if(count($filteredApplications) === 0)
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('ui.empty_state_description')"
                icon="📋"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] table-fixed border-collapse text-start">
                    <colgroup>
                        <col class="w-[26%]">
                        <col class="w-[20%]">
                        <col class="w-[14%]">
                        <col class="w-[14%]">
                        <col class="w-[12%]">
                        <col class="w-[14%]">
                    </colgroup>
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_blogger') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_platforms') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_followers') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_niche') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_status') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('admin.applications.table_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($filteredApplications as $application)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="application-{{ $application['id'] }}">
                                <td class="py-3.5 pe-4 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br {{ $application['gradient'] }} text-sm font-bold text-white">
                                            {{ mb_substr($application['name'], 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate font-semibold">{{ $application['name'] }}</div>
                                            <div class="truncate text-xs text-surface-muted dark:text-brand-muted">{{ $application['email'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 pe-4 align-middle">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($application['platforms'] as $platform)
                                            <span class="inline-flex items-center gap-1 rounded-md border border-surface-border bg-surface-card-alt px-2 py-0.5 text-[11px] text-surface-text dark:border-transparent dark:bg-brand-card-alt dark:text-brand-text">
                                                {{ $platformIcons[$platform] ?? '' }} {{ __('ui.platforms.'.$platform) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3.5 pe-4 align-middle">{{ __('ui.followers.'.$application['followers']) }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ __('ui.niches.'.$application['niche']) }}</td>
                                <td class="py-3.5 pe-4 align-middle">
                                    <x-ui.badge :type="$application['status']">
                                        @if($application['status'] === 'pending')
                                            ⏳ {{ __('ui.status_pending') }}
                                        @elseif($application['status'] === 'approved')
                                            ✅ {{ __('ui.status_approved') }}
                                        @else
                                            ❌ {{ __('ui.status_rejected') }}
                                        @endif
                                    </x-ui.badge>
                                </td>
                                <td class="py-3.5 align-middle">
                                    <div class="flex flex-wrap gap-1.5">
                                        <x-ui.button variant="secondary" size="sm" wire:click="viewApplication('{{ $application['id'] }}')">
                                            👁 {{ __('ui.view') }}
                                        </x-ui.button>
                                        @if($application['status'] === 'pending')
                                            <x-ui.button variant="success" size="sm" wire:click="approve('{{ $application['id'] }}')">
                                                ✅ {{ __('ui.approve') }}
                                            </x-ui.button>
                                            <x-ui.button variant="danger" size="sm" wire:click="reject('{{ $application['id'] }}')">
                                                ❌ {{ __('ui.reject') }}
                                            </x-ui.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- View Modal --}}
    <x-ui.modal wire:model="showModal" :title="__('admin.applications.modal_title')">
        @if($viewingApplication)
            <div class="space-y-3 text-sm text-surface-text dark:text-brand-text">
                <div class="flex items-center justify-between border-b border-surface-border py-2.5 dark:border-brand-border/40">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('apply.form.first_name') }}</span>
                    <span class="font-semibold">{{ $viewingApplication['name'] }}</span>
                </div>
                <div class="flex items-center justify-between border-b border-surface-border py-2.5 dark:border-brand-border/40">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('apply.form.email') }}</span>
                    <span class="font-semibold">{{ $viewingApplication['email'] }}</span>
                </div>
                <div class="flex items-center justify-between border-b border-surface-border py-2.5 dark:border-brand-border/40">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('apply.form.phone') }}</span>
                    <span class="font-semibold">{{ $viewingApplication['phone'] }}</span>
                </div>
                <div class="flex items-center justify-between border-b border-surface-border py-2.5 dark:border-brand-border/40">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('admin.applications.table_platforms') }}</span>
                    <span class="font-semibold">
                        @foreach($viewingApplication['platforms'] as $platform)
                            {{ $platformIcons[$platform] ?? '' }} {{ __('ui.platforms.'.$platform) }}@if(! $loop->last), @endif
                        @endforeach
                    </span>
                </div>
                <div class="flex items-center justify-between border-b border-surface-border py-2.5 dark:border-brand-border/40">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('admin.applications.table_followers') }}</span>
                    <span class="font-semibold">{{ __('ui.followers.'.$viewingApplication['followers']) }}</span>
                </div>
                <div class="flex items-center justify-between border-b border-surface-border py-2.5 dark:border-brand-border/40">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('admin.applications.table_niche') }}</span>
                    <span class="font-semibold">{{ __('ui.niches.'.$viewingApplication['niche']) }}</span>
                </div>
                <div class="flex items-center justify-between border-b border-surface-border py-2.5 dark:border-brand-border/40">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('apply.form.country') }}</span>
                    <span class="font-semibold">{{ $viewingApplication['country_label'] ?? $viewingApplication['country'] }}</span>
                </div>
                @if(filled($viewingApplication['about']))
                    <div class="flex items-start justify-between border-b border-surface-border py-2.5 dark:border-brand-border/40">
                        <span class="shrink-0 text-surface-muted dark:text-brand-muted">{{ __('admin.applications.about') }}</span>
                        <span class="max-w-[260px] text-end font-semibold">{{ $viewingApplication['about'] }}</span>
                    </div>
                @endif
                @if(! empty($viewingApplication['promo']))
                    <div class="flex items-center justify-between border-b border-surface-border py-2.5 dark:border-brand-border/40">
                        <span class="text-surface-muted dark:text-brand-muted">{{ __('admin.partners.table_promo') }}</span>
                        <span class="font-bold tracking-widest text-brand-cyan">{{ $viewingApplication['promo'] }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between border-b border-surface-border py-2.5 dark:border-brand-border/40">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('admin.applications.applied_at') }}</span>
                    <span class="font-semibold">{{ $viewingApplication['date'] }}</span>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('ui.status') }}</span>
                    <x-ui.badge :type="$viewingApplication['status']">
                        @if($viewingApplication['status'] === 'pending')
                            ⏳ {{ __('ui.status_pending') }}
                        @elseif($viewingApplication['status'] === 'approved')
                            ✅ {{ __('ui.status_approved') }}
                        @else
                            ❌ {{ __('ui.status_rejected') }}
                        @endif
                    </x-ui.badge>
                </div>
            </div>

            @if($viewingApplication['status'] === 'pending')
                <div class="mt-6 rounded-xl border border-surface-border bg-surface-card-alt p-4 dark:border-brand-border dark:bg-brand-card-alt">
                    <x-ui.form-group :label="__('admin.applications.assign_promo')">
                        <div class="flex gap-2">
                            <x-ui.input wire:model="promoCode" placeholder="SARDOR10" class="flex-1 uppercase" />
                            <x-ui.button variant="outline" wire:click="generatePromo">
                                ⚡ {{ __('ui.generate') }}
                            </x-ui.button>
                        </div>
                    </x-ui.form-group>
                </div>

                <div class="mt-5 flex gap-3">
                    <x-ui.button variant="success" class="flex-1" wire:click="approveFromModal">
                        ✅ {{ __('admin.applications.approve_with_promo') }}
                    </x-ui.button>
                    <x-ui.button variant="danger" class="flex-1" wire:click="rejectFromModal">
                        ❌ {{ __('ui.reject') }}
                    </x-ui.button>
                </div>
            @else
                <div class="mt-5">
                    <x-ui.button variant="secondary" class="w-full" wire:click="closeModal">
                        {{ __('ui.close') }}
                    </x-ui.button>
                </div>
            @endif
        @endif
    </x-ui.modal>
</div>
