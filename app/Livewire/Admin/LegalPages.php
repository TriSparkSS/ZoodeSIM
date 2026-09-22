<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\ContentBlock;
use App\Services\Locale\LocaleManager;
use Livewire\Component;

class LegalPages extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public string $editLocale = 'en';

    public ?string $editingBlockId = null;

    public bool $showEditor = false;

    public string $blockTitle = '';

    public string $blockBody = '';

    public bool $blockIsActive = true;

    protected LocaleManager $locales;

    public function boot(LocaleManager $locales): void
    {
        $this->locales = $locales;
    }

    public function mount(): void
    {
        $this->editLocale = $this->locales->current();
    }

    public function openEditor(string $blockId): void
    {
        $block = ContentBlock::query()->legal()->whereKey($blockId)->first();

        if (! $block) {
            return;
        }

        $this->resetValidation();
        $this->editingBlockId = $block->id;
        $this->blockIsActive = (bool) $block->is_active;
        $this->loadTranslations($block);
        $this->showEditor = true;
    }

    public function updatedEditLocale(): void
    {
        if (! $this->locales->isSupported($this->editLocale)) {
            $this->editLocale = $this->locales->fallback();
        }

        if ($this->editingBlockId && $this->showEditor) {
            $block = ContentBlock::query()->legal()->whereKey($this->editingBlockId)->first();
            if ($block) {
                $this->loadTranslations($block);
            }
        }
    }

    public function save(): void
    {
        if (! $this->editingBlockId) {
            return;
        }

        $this->validate([
            'editLocale' => ['required', 'in:'.implode(',', $this->locales->codes())],
            'blockTitle' => ['required', 'string', 'max:255'],
            'blockBody' => ['required', 'string', 'max:100000'],
            'blockIsActive' => ['boolean'],
        ]);

        $block = ContentBlock::query()->legal()->whereKey($this->editingBlockId)->first();

        if (! $block) {
            return;
        }

        $block->setTranslation('title', $this->editLocale, $this->blockTitle);
        $block->setTranslation('body', $this->editLocale, $this->blockBody);
        $block->is_active = $this->blockIsActive;
        $block->save();

        $this->showEditor = false;
        $this->editingBlockId = null;
        $this->toast(__('admin.legal_pages.saved'));
    }

    public function closeEditor(): void
    {
        $this->showEditor = false;
        $this->editingBlockId = null;
        $this->blockTitle = '';
        $this->blockBody = '';
        $this->resetValidation();
    }

    protected function loadTranslations(ContentBlock $block): void
    {
        $this->blockTitle = (string) $block->getTranslation('title', $this->editLocale, true);
        $this->blockBody = (string) $block->getTranslation('body', $this->editLocale, true);
    }

    public function render()
    {
        return $this->withLocalizedTitle(
            view('livewire.admin.legal-pages', [
                'pages' => ContentBlock::query()->legal()->orderBy('slug')->get(),
                'localeOptions' => $this->locales->supported(),
                'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.legal_pages')),
            ])->layout('layouts.admin', [
                'navItems' => $this->adminNavItems(),
            ]),
            'admin.legal_pages.title'
        );
    }
}
