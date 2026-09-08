<div>
    <x-ui.page-header
        :title="__('partner.registrations.title')"
        :subtitle="__('partner.registrations.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <!-- Stats -->
    <div class="mb-7 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.stat-card
            :label="__('partner.registrations.total')"
            :value="(string) $totalCount"
            color="cyan"
        />
        <x-ui.stat-card
            :label="__('partner.registrations.this_month')"
            :value="(string) $thisMonthCount"
            :change="now()->translatedFormat('F Y')"
            change-type="neutral"
            color="purple"
        />
    </div>

    <div class="mb-7 grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-end">
        <div class="relative">
            <x-ui.search-input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('partner.registrations.search_placeholder')"
            />
            <div wire:loading wire:target="search" class="absolute end-3 top-1/2 -translate-y-1/2">
                <span class="h-4 w-4 animate-spin rounded-full border-2 border-brand-cyan border-t-transparent"></span>
            </div>
        </div>
        <x-ui.form-group :label="__('partner.registrations.date_from')" name="dateFrom" class="md:w-44">
            <x-ui.input type="date" name="dateFrom" wire:model.live="dateFrom" />
        </x-ui.form-group>
        <x-ui.form-group :label="__('partner.registrations.date_to')" name="dateTo" class="md:w-44">
            <x-ui.input type="date" name="dateTo" wire:model.live="dateTo" />
        </x-ui.form-group>
    </div>

    <!-- Registrations List -->
    <x-ui.card :title="__('partner.registrations.title')">
        @if(count($registrations) > 0)
            <div class="space-y-2.5">
                @foreach($registrations as $reg)
                    <div class="flex items-center justify-between rounded-xl border border-surface-border bg-surface-card-alt/70 px-3.5 py-3 dark:border-transparent dark:bg-brand-card-alt">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br {{ $reg['gradient'] }} text-xs font-bold text-white">
                                {{ $reg['initial'] }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-surface-text dark:text-brand-text">{{ $reg['name'] }}</div>
                                <div class="text-[11px] text-surface-muted dark:text-brand-muted">
                                    {{ $reg['date'] }} · {{ $reg['time'] }} · {{ __('partner.dashboard.used_code', ['code' => $reg['code']]) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-ui.badge type="pending">{{ __('partner.registrations.status_registered') }}</x-ui.badge>
                            <x-ui.badge type="approved">+{{ $reg['bonus'] }}</x-ui.badge>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <x-ui.pagination
                :current="1"
                :total="1"
                :item-count="count($registrations)"
                class="mt-6"
            />
        @else
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('partner.registrations.search_placeholder')"
                icon="🔍"
            />
        @endif
    </x-ui.card>
</div>
