<div>
    <x-ui.page-header
        :title="__('partner.inbox.title')"
        :subtitle="__('partner.inbox.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <x-ui.card :title="__('partner.inbox.title')">
        <div class="mb-4 flex items-center justify-between gap-3">
            <p class="text-sm text-surface-muted dark:text-brand-muted">
                {{ __('partner.inbox.unread', ['count' => $unreadCount]) }}
            </p>
            @if($unreadCount > 0)
                <x-ui.button type="button" size="sm" wire:click="markAllRead">
                    {{ __('partner.inbox.mark_all_read') }}
                </x-ui.button>
            @endif
        </div>

        @if($notifications->isEmpty())
            <x-ui.empty-state :title="__('ui.no_data')" :description="__('partner.inbox.empty')" />
        @else
            <ul class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                @foreach($notifications as $notification)
                    @php
                        $data = is_array($notification->data) ? $notification->data : [];
                    @endphp
                    <li class="flex items-start justify-between gap-4 py-4">
                        <div>
                            <div class="text-sm font-semibold text-surface-text dark:text-brand-text">
                                {{ $data['title'] ?? __('ui.notifications') }}
                            </div>
                            <p class="mt-1 text-sm text-surface-muted dark:text-brand-muted">
                                {{ $data['body'] ?? '' }}
                            </p>
                            <div class="mt-1 text-[11px] text-surface-muted dark:text-brand-muted">
                                {{ $notification->created_at?->format('Y-m-d H:i') }}
                            </div>
                        </div>
                        @if($notification->read_at === null)
                            <x-ui.button type="button" size="sm" variant="ghost" wire:click="markRead('{{ $notification->id }}')">
                                {{ __('partner.inbox.mark_read') }}
                            </x-ui.button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
</div>
