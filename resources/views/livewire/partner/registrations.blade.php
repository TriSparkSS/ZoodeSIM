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
            :change="'↑ '.__('partner.dashboard.this_week', ['count' => 23])"
            color="cyan"
        />
        <x-ui.stat-card
            :label="__('partner.registrations.this_month')"
            :value="'34'"
            :change="now()->translatedFormat('F Y')"
            change-type="neutral"
            color="purple"
        />
    </div>

    <!-- Search -->
    <div class="relative mb-7">
        <x-ui.search-input
            wire:model.live.debounce.300ms="search"
            :placeholder="__('partner.registrations.search_placeholder')"
        />
        <div wire:loading wire:target="search" class="absolute end-3 top-1/2 -translate-y-1/2">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-brand-cyan border-t-transparent"></span>
        </div>
    </div>

    <!-- Registrations List -->
    <x-ui.card :title="__('partner.registrations.title')">
        @if(count($registrations) > 0)
            <div class="space-y-2.5">
                @foreach($registrations as $reg)
                    <div class="flex items-center justify-between rounded-xl bg-brand-card-alt px-3.5 py-3 dark:bg-brand-card-alt">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br {{ $reg['gradient'] }} text-xs font-bold text-white">
                                {{ $reg['initial'] }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold">{{ $reg['name'] }}</div>
                                <div class="text-[11px] text-brand-muted">
                                    {{ __('ui.time.'.$reg['time']) }} · {{ __('partner.dashboard.used_code', ['code' => $reg['code']]) }}
                                </div>
                            </div>
                        </div>
                        <x-ui.badge type="approved">+{{ $reg['bonus'] }}</x-ui.badge>
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
