<x-ui.card :title="__('admin.statistics.recent_applications')" :action="__('ui.view_all')" :action-href="route('admin.applications')">
    @if(count($recentApplications) === 0)
        <x-ui.empty-state
            :title="__('ui.no_data')"
            :description="__('ui.empty_state_description')"
        />
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] table-fixed border-collapse text-start">
                <thead>
                    <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_blogger') }}</th>
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_platforms') }}</th>
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_followers') }}</th>
                        <th class="pb-3.5 text-start font-medium">{{ __('ui.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                    @foreach($recentApplications as $application)
                        <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="app-{{ $application['id'] }}">
                            <td class="py-3.5 pe-4 align-middle">
                                <div class="font-semibold">{{ $application['name'] }}</div>
                                <div class="text-xs text-surface-muted dark:text-brand-muted">{{ $application['email'] }}</div>
                            </td>
                            <td class="py-3.5 pe-4 align-middle">
                                @forelse($application['platforms'] as $platform)
                                    <span class="text-[11px]">{{ __('ui.platforms.'.$platform) }}@if(! $loop->last), @endif</span>
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td class="py-3.5 pe-4 align-middle">
                                {{ $application['followers'] ? __('ui.followers.'.$application['followers']) : '—' }}
                            </td>
                            <td class="py-3.5 align-middle">
                                <x-ui.badge :type="$application['status']">
                                    {{ __('ui.status_'.$application['status']) }}
                                </x-ui.badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
