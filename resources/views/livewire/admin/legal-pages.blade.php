<div>
    <x-ui.page-header
        :title="__('admin.legal_pages.title')"
        :subtitle="__('admin.legal_pages.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <x-ui.card>
        @if($pages->isEmpty())
            <x-ui.empty-state
                :title="__('ui.no_data')"
                :description="__('admin.legal_pages.empty')"
            />
        @else
            <div class="space-y-3">
                @foreach($pages as $block)
                    <div
                        class="flex flex-col gap-3 rounded-xl border border-surface-border p-4 sm:flex-row sm:items-center sm:justify-between dark:border-brand-border"
                        wire:key="legal-{{ $block->id }}"
                    >
                        <div class="min-w-0">
                            <div class="text-xs font-medium uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ $block->slug }}</div>
                            <div class="mt-1 truncate font-semibold text-surface-text dark:text-brand-text">{{ $block->title }}</div>
                            <div class="mt-1 text-xs text-surface-muted dark:text-brand-muted">
                                {{ $block->is_active ? __('ui.status_active') : __('ui.status_inactive') }}
                            </div>
                        </div>
                        <x-ui.button variant="secondary" size="sm" wire:click="openEditor('{{ $block->id }}')">
                            {{ __('ui.edit') }}
                        </x-ui.button>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.modal wire:model="showEditor" :title="__('admin.legal_pages.edit_title')" maxWidth="max-w-3xl">
        <div class="space-y-5 text-sm">
            <x-ui.form-group :label="__('ui.language')" required>
                <x-ui.select wire:model.live="editLocale">
                    @foreach($localeOptions as $code => $meta)
                        <option value="{{ $code }}">{{ $meta['native'] }} ({{ strtoupper($code) }})</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>

            <x-ui.form-group :label="__('admin.legal_pages.block_title')" required>
                <x-ui.input wire:model="blockTitle" />
                @error('blockTitle')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <x-ui.form-group :label="__('admin.legal_pages.block_body')" required>
                <textarea
                    wire:model="blockBody"
                    rows="16"
                    class="w-full rounded-xl border border-surface-border bg-surface-card px-3 py-2 text-sm text-surface-text focus:border-brand-cyan focus:outline-none focus:ring-1 focus:ring-brand-cyan dark:border-brand-border dark:bg-brand-card dark:text-brand-text"
                ></textarea>
                @error('blockBody')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <label class="flex items-center gap-2 text-sm text-surface-text dark:text-brand-text">
                <input type="checkbox" wire:model="blockIsActive" class="rounded border-surface-border text-brand-cyan focus:ring-brand-cyan">
                {{ __('ui.status_active') }}
            </label>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button variant="secondary" class="flex-1" wire:click="closeEditor">
                    {{ __('ui.cancel') }}
                </x-ui.button>
                <x-ui.button variant="success" class="flex-1" wire:click="save">
                    {{ __('ui.save') }}
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
