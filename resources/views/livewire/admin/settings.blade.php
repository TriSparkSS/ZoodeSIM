<div>
    <x-ui.page-header
        :title="__('admin.settings.title')"
        :subtitle="__('admin.settings.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <div class="space-y-6">
        <x-ui.card :title="__('admin.settings.program')">
            <form wire:submit="save" class="max-w-xl space-y-5">
                @forelse($programSettings as $setting)
                    <x-ui.form-group
                        :label="$setting->label"
                        :name="'settingValues.'.$setting->key"
                    >
                        @if(filled($setting->description))
                            <p class="mb-2 text-xs text-surface-muted dark:text-brand-muted">{{ $setting->description }}</p>
                        @endif
                        <x-ui.input
                            wire:model="settingValues.{{ $setting->key }}"
                            name="settingValues.{{ $setting->key }}"
                            type="text"
                        />
                        @error('settingValues.'.$setting->key)
                            <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                        @enderror
                    </x-ui.form-group>
                @empty
                    <p class="text-sm text-surface-muted dark:text-brand-muted">{{ __('admin.settings.no_settings') }}</p>
                @endforelse

                @if($programSettings->isNotEmpty())
                    <x-ui.button type="submit">
                        💾 {{ __('ui.save') }}
                    </x-ui.button>
                @endif
            </form>
        </x-ui.card>

        <x-ui.card :title="__('admin.settings.content_title')">
            <p class="mb-4 text-sm text-surface-muted dark:text-brand-muted">{{ __('admin.settings.content_subtitle') }}</p>

            @if($contentBlocks->isEmpty())
                <x-ui.empty-state
                    :title="__('ui.no_data')"
                    :description="__('admin.settings.no_content')"
                />
            @else
                <div class="space-y-3">
                    @foreach($contentBlocks as $block)
                        <div
                            class="flex flex-col gap-3 rounded-xl border border-surface-border p-4 sm:flex-row sm:items-center sm:justify-between dark:border-brand-border"
                            wire:key="content-{{ $block->id }}"
                        >
                            <div class="min-w-0">
                                <div class="text-xs font-medium uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ $block->slug }}</div>
                                <div class="mt-1 truncate font-semibold text-surface-text dark:text-brand-text">{{ $block->title }}</div>
                                @if(filled($block->body))
                                    <div class="mt-1 line-clamp-2 text-sm text-surface-muted dark:text-brand-muted">{{ $block->body }}</div>
                                @endif
                            </div>
                            <x-ui.button variant="secondary" size="sm" wire:click="openContentEditor('{{ $block->id }}')">
                                {{ __('ui.edit') }}
                            </x-ui.button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.modal wire:model="showContentModal" :title="__('admin.settings.content_edit_title')" maxWidth="max-w-2xl">
        <div class="space-y-5 text-sm">
            <x-ui.form-group :label="__('ui.language')" required>
                <x-ui.select wire:model.live="editLocale">
                    @foreach($localeOptions as $code => $meta)
                        <option value="{{ $code }}">{{ $meta['native'] }} ({{ strtoupper($code) }})</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>

            <x-ui.form-group :label="__('admin.settings.content_block_title')" required>
                <x-ui.input wire:model="blockTitle" />
                @error('blockTitle')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <x-ui.form-group :label="__('admin.settings.content_block_body')">
                <textarea
                    wire:model="blockBody"
                    rows="4"
                    class="w-full rounded-xl border border-surface-border bg-surface-card px-3 py-2 text-sm text-surface-text focus:border-brand-cyan focus:outline-none focus:ring-1 focus:ring-brand-cyan dark:border-brand-border dark:bg-brand-card dark:text-brand-text"
                ></textarea>
                @error('blockBody')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button variant="secondary" class="flex-1" wire:click="closeContentModal">
                    {{ __('ui.cancel') }}
                </x-ui.button>
                <x-ui.button variant="success" class="flex-1" wire:click="saveContentBlock">
                    {{ __('ui.save') }}
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
