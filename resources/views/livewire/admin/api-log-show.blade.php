<div>
    <x-ui.page-header
        :title="__('admin.api_logs.details_title')"
        :subtitle="$log['method'].' '.$log['endpoint']"
        :breadcrumbs="$breadcrumbs"
    >
        <x-slot:actions>
            <a
                href="{{ route('admin.api-logs') }}"
                class="inline-flex items-center justify-center rounded-xl border border-surface-border bg-surface-card px-4 py-2.5 text-sm font-semibold text-surface-text transition hover:border-brand-cyan/40 dark:border-brand-border dark:bg-brand-card-alt dark:text-brand-text"
            >
                {{ __('admin.api_logs.back') }}
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6">
        <x-ui.card>
            <h2 class="text-sm font-semibold text-surface-text dark:text-brand-text">{{ __('admin.api_logs.section_request') }}</h2>
            <div class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.api_logs.table_type') }}</div>
                    <div class="mt-1 font-medium">{{ __('admin.api_logs.types.'.$log['type']) }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.api_logs.table_service') }}</div>
                    <div class="mt-1 font-medium">{{ $log['service'] }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.api_logs.table_method') }}</div>
                    <div class="mt-1 font-medium">{{ $log['method'] }}</div>
                </div>
                <div class="sm:col-span-2">
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.api_logs.table_url') }}</div>
                    <div class="mt-1 break-all font-medium">{{ $log['full_url'] }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.api_logs.table_time') }}</div>
                    <div class="mt-1 font-medium">{{ $log['created_at'] }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.api_logs.table_ip') }}</div>
                    <div class="mt-1 font-medium">{{ $log['ip_address'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.api_logs.table_user') }}</div>
                    <div class="mt-1 font-medium">{{ $log['user'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.api_logs.table_reference') }}</div>
                    <div class="mt-1 font-medium">{{ $log['reference'] ?? '—' }}</div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-sm font-semibold text-surface-text dark:text-brand-text">{{ __('admin.api_logs.section_response') }}</h2>
            <div class="mt-4 flex flex-wrap gap-6 text-sm">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.api_logs.table_status') }}</div>
                    <div class="mt-1">
                        <x-ui.badge :type="$log['failed'] ? 'rejected' : 'active'">
                            {{ $log['response_status'] ?? '—' }}
                        </x-ui.badge>
                    </div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.api_logs.table_time_ms') }}</div>
                    <div class="mt-1 font-medium">{{ number_format($log['response_time_ms']) }}ms</div>
                </div>
            </div>
        </x-ui.card>

        @if($log['error_message'])
            <x-ui.card>
                <h2 class="text-sm font-semibold text-brand-red">{{ __('admin.api_logs.section_error') }}</h2>
                <p class="mt-3 break-all text-sm text-surface-text dark:text-brand-text">{{ $log['error_message'] }}</p>
            </x-ui.card>
        @endif

        @foreach([
            'request_headers' => __('admin.api_logs.section_request_headers'),
            'request_body' => __('admin.api_logs.section_request_body'),
            'response_headers' => __('admin.api_logs.section_response_headers'),
            'response_body' => __('admin.api_logs.section_response_body'),
        ] as $key => $title)
            <div
                x-data="{ open: {{ in_array($key, ['request_body', 'response_body'], true) ? 'true' : 'false' }} }"
                class="light-card overflow-hidden"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between p-5 text-start text-sm font-semibold text-surface-text dark:text-brand-text"
                    @click="open = ! open"
                >
                    <span>{{ $title }}</span>
                    <span class="text-surface-muted dark:text-brand-muted" x-text="open ? '−' : '+'"></span>
                </button>
                <div x-show="open" x-cloak class="border-t border-surface-border px-5 pb-5 dark:border-brand-border">
                    <pre class="mt-4 max-h-[28rem] overflow-auto rounded-xl bg-surface-card-alt p-4 text-xs text-surface-text dark:bg-brand-card-alt dark:text-brand-text">{{ $log[$key] !== '' ? $log[$key] : '—' }}</pre>
                </div>
            </div>
        @endforeach
    </div>
</div>
