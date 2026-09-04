<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\ContentBlock;
use App\Models\ProgramSetting;
use App\Services\Content\ProgramSettingService;
use App\Services\Locale\LocaleManager;
use Livewire\Component;

class Settings extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    /** @var array<string, string> */
    public array $settingValues = [];

    public string $editLocale = 'en';

    public ?string $editingBlockId = null;

    public bool $showContentModal = false;

    public string $blockTitle = '';

    public string $blockBody = '';

    protected ProgramSettingService $settings;

    protected LocaleManager $locales;

    public function boot(ProgramSettingService $settings, LocaleManager $locales): void
    {
        $this->settings = $settings;
        $this->locales = $locales;
    }

    public function mount(): void
    {
        $this->editLocale = $this->locales->current();
        $this->loadSettingValues();
    }

    public function save(): void
    {
        $this->validate([
            'settingValues.*' => ['required', 'string', 'max:255'],
        ]);

        $this->settings->updateValues($this->settingValues);
        $this->toast(__('admin.settings.saved'));
    }

    public function openContentEditor(string $blockId): void
    {
        $block = ContentBlock::query()->whereKey($blockId)->first();

        if (! $block) {
            return;
        }

        $this->editingBlockId = $block->id;
        $this->blockTitle = (string) $block->getTranslation('title', $this->editLocale, true);
        $this->blockBody = (string) $block->getTranslation('body', $this->editLocale, true);
        $this->showContentModal = true;
    }

    public function updatedEditLocale(): void
    {
        if (! $this->locales->isSupported($this->editLocale)) {
            $this->editLocale = $this->locales->fallback();
        }

        if ($this->editingBlockId && $this->showContentModal) {
            $block = ContentBlock::query()->whereKey($this->editingBlockId)->first();
            if ($block) {
                $this->blockTitle = (string) $block->getTranslation('title', $this->editLocale, true);
                $this->blockBody = (string) $block->getTranslation('body', $this->editLocale, true);
            }
        }
    }

    public function saveContentBlock(): void
    {
        if (! $this->editingBlockId) {
            return;
        }

        $this->validate([
            'editLocale' => ['required', 'in:'.implode(',', $this->locales->codes())],
            'blockTitle' => ['required', 'string', 'max:255'],
            'blockBody' => ['nullable', 'string', 'max:5000'],
        ]);

        $block = ContentBlock::query()->whereKey($this->editingBlockId)->first();

        if (! $block) {
            return;
        }

        $block->setTranslation('title', $this->editLocale, $this->blockTitle);
        $block->setTranslation('body', $this->editLocale, $this->blockBody);
        $block->save();

        $this->showContentModal = false;
        $this->editingBlockId = null;
        $this->toast(__('admin.settings.content_saved'));
    }

    public function closeContentModal(): void
    {
        $this->showContentModal = false;
        $this->editingBlockId = null;
        $this->blockTitle = '';
        $this->blockBody = '';
    }

    protected function loadSettingValues(): void
    {
        $this->settingValues = $this->settings->allOrdered()
            ->mapWithKeys(fn (ProgramSetting $setting) => [$setting->key => (string) $setting->value])
            ->all();
    }

    public function render()
    {
        return $this->withLocalizedTitle(
            view('livewire.admin.settings', [
                'programSettings' => $this->settings->allOrdered(),
                'contentBlocks' => ContentBlock::query()->orderBy('slug')->get(),
                'localeOptions' => $this->locales->supported(),
                'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.settings')),
            ])->layout('layouts.admin', [
                'navItems' => $this->adminNavItems(),
            ]),
            'admin.settings.title'
        );
    }
}
