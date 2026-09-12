<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\Banner;
use App\Services\Banner\Contracts\BannerServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Banners extends Component
{
    use WithAdminNavigation;
    use WithFileUploads;
    use WithLocalizedTitle;
    use WithToast;

    public string $statusFilter = 'all';

    public string $displayFilter = 'all';

    public bool $showFormModal = false;

    public ?string $editingId = null;

    public string $formTitle = '';

    public string $formLinkUrl = '';

    public string $formDisplayOn = Banner::DISPLAY_USER_APP;

    public string $formSortOrder = '0';

    public bool $formIsActive = true;

    public mixed $formImage = null;

    protected BannerServiceInterface $banners;

    public function boot(BannerServiceInterface $banners): void
    {
        $this->banners = $banners;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(string $id): void
    {
        $banner = Banner::query()->whereKey($id)->first();

        if (! $banner) {
            return;
        }

        $this->resetValidation();
        $this->formImage = null;
        $this->editingId = $banner->id;
        $this->formTitle = $banner->title;
        $this->formLinkUrl = (string) ($banner->link_url ?? '');
        $this->formDisplayOn = $banner->display_on;
        $this->formSortOrder = (string) $banner->sort_order;
        $this->formIsActive = $banner->is_active;
        $this->showFormModal = true;
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $imageRules = $this->editingId
            ? ['nullable', 'file', 'max:4096', 'mimes:jpeg,jpg,png,webp']
            : ['required', 'file', 'max:4096', 'mimes:jpeg,jpg,png,webp'];

        $validated = $this->validate([
            'formTitle' => ['required', 'string', 'max:120'],
            'formLinkUrl' => ['nullable', 'url', 'max:2048'],
            'formDisplayOn' => ['required', Rule::in(Banner::displayTargets())],
            'formSortOrder' => ['required', 'integer', 'min:0', 'max:9999'],
            'formIsActive' => ['boolean'],
            'formImage' => $imageRules,
        ], [
            'formTitle.required' => __('admin.banners.validation.title_required'),
            'formLinkUrl.url' => __('admin.banners.validation.link_url'),
            'formDisplayOn.required' => __('admin.banners.validation.display_on'),
            'formDisplayOn.in' => __('admin.banners.validation.display_on'),
            'formSortOrder.required' => __('admin.banners.validation.sort_required'),
            'formSortOrder.integer' => __('admin.banners.validation.sort_invalid'),
            'formImage.required' => __('admin.banners.validation.image_required'),
            'formImage.mimes' => __('admin.banners.validation.image_type'),
            'formImage.max' => __('admin.banners.validation.image_max'),
        ]);

        $payload = [
            'title' => $validated['formTitle'],
            'link_url' => $validated['formLinkUrl'] ?? null,
            'display_on' => $validated['formDisplayOn'],
            'sort_order' => (int) $validated['formSortOrder'],
            'is_active' => (bool) $validated['formIsActive'],
        ];

        $image = $this->formImage instanceof UploadedFile ? $this->formImage : null;

        if ($this->editingId) {
            $banner = Banner::query()->whereKey($this->editingId)->first();

            if (! $banner) {
                return;
            }

            $this->banners->update($banner, $payload, $image);
            $this->toast(__('admin.banners.updated_toast'));
        } else {
            if ($image === null) {
                $this->addError('formImage', __('admin.banners.validation.image_required'));

                return;
            }

            $this->banners->create($payload, $image);
            $this->toast(__('admin.banners.created_toast'));
        }

        $this->closeModal();
    }

    public function toggle(string $id): void
    {
        $banner = Banner::query()->whereKey($id)->first();

        if (! $banner) {
            return;
        }

        $updated = $this->banners->toggle($banner);
        $this->toast($updated->is_active
            ? __('admin.banners.activated_toast')
            : __('admin.banners.deactivated_toast'));
    }

    public function delete(string $id): void
    {
        $banner = Banner::query()->whereKey($id)->first();

        if (! $banner) {
            return;
        }

        $this->banners->delete($banner);
        $this->toast(__('admin.banners.deleted_toast'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function banners(): array
    {
        $rows = $this->banners->list()->map(function (Banner $banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => $banner->imageUrl(),
                'link_url' => $banner->link_url,
                'display_on' => $banner->display_on,
                'sort_order' => $banner->sort_order,
                'is_active' => $banner->is_active,
            ];
        })->all();

        if ($this->statusFilter === 'active') {
            $rows = array_values(array_filter($rows, fn (array $row) => $row['is_active']));
        }

        if ($this->statusFilter === 'inactive') {
            $rows = array_values(array_filter($rows, fn (array $row) => ! $row['is_active']));
        }

        if ($this->displayFilter !== 'all') {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row) => $row['display_on'] === $this->displayFilter
            ));
        }

        return $rows;
    }

    public function editingImageUrl(): ?string
    {
        if (! $this->editingId) {
            return null;
        }

        return Banner::query()->whereKey($this->editingId)->first()?->imageUrl();
    }

    protected function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->formTitle = '';
        $this->formLinkUrl = '';
        $this->formDisplayOn = Banner::DISPLAY_USER_APP;
        $this->formSortOrder = '0';
        $this->formIsActive = true;
        $this->formImage = null;
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.banners', [
            'banners' => $this->banners(),
            'editingImageUrl' => $this->editingImageUrl(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.banners')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.banners');
    }
}
