<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\Country;
use App\Services\Country\Contracts\CountryServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Countries extends Component
{
    use WithAdminNavigation;
    use WithFileUploads;
    use WithLocalizedTitle;
    use WithToast;

    public string $statusFilter = 'all';

    public bool $showFormModal = false;

    public ?string $editingId = null;

    public string $formCode = '';

    public string $formName = '';

    public string $formSortOrder = '0';

    public bool $formIsActive = true;

    public mixed $formImage = null;

    protected CountryServiceInterface $countries;

    public function boot(CountryServiceInterface $countries): void
    {
        $this->countries = $countries;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(string $id): void
    {
        $country = Country::query()->whereKey($id)->first();

        if (! $country) {
            return;
        }

        $this->resetValidation();
        $this->formImage = null;
        $this->editingId = $country->id;
        $this->formCode = $country->code;
        $this->formName = $country->name;
        $this->formSortOrder = (string) $country->sort_order;
        $this->formIsActive = $country->is_active;
        $this->showFormModal = true;
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->formCode = strtoupper(trim($this->formCode));

        $imageRules = ['nullable', 'file', 'max:2048', 'mimes:jpeg,jpg,png,webp,svg'];

        $validated = $this->validate([
            'formCode' => [
                'required',
                'string',
                'size:2',
                'regex:/^[A-Z]{2}$/',
                Rule::unique('countries', 'code')->ignore($this->editingId),
            ],
            'formName' => ['required', 'string', 'max:100'],
            'formSortOrder' => ['required', 'integer', 'min:0', 'max:9999'],
            'formIsActive' => ['boolean'],
            'formImage' => $imageRules,
        ], [
            'formCode.required' => __('admin.countries.validation.code_required'),
            'formCode.size' => __('admin.countries.validation.code_format'),
            'formCode.regex' => __('admin.countries.validation.code_format'),
            'formCode.unique' => __('admin.countries.validation.code_taken'),
            'formName.required' => __('admin.countries.validation.name_required'),
            'formSortOrder.required' => __('admin.countries.validation.sort_required'),
            'formSortOrder.integer' => __('admin.countries.validation.sort_invalid'),
            'formImage.mimes' => __('admin.countries.validation.image_type'),
            'formImage.max' => __('admin.countries.validation.image_max'),
        ]);

        $payload = [
            'code' => $validated['formCode'],
            'name' => $validated['formName'],
            'sort_order' => (int) $validated['formSortOrder'],
            'is_active' => (bool) $validated['formIsActive'],
        ];

        $image = $this->formImage instanceof UploadedFile ? $this->formImage : null;

        try {
            if ($this->editingId) {
                $country = Country::query()->whereKey($this->editingId)->first();

                if (! $country) {
                    return;
                }

                $this->countries->update($country, $payload, $image);
                $this->toast(__('admin.countries.updated_toast'));
            } else {
                $this->countries->create($payload, $image);
                $this->toast(__('admin.countries.created_toast'));
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            $this->toast(collect($e->errors())->flatten()->first() ?: __('admin.countries.validation.code_taken'), 'error');

            return;
        }

        $this->closeModal();
    }

    public function toggle(string $id): void
    {
        $country = Country::query()->whereKey($id)->first();

        if (! $country) {
            return;
        }

        $updated = $this->countries->toggle($country);
        $this->toast($updated->is_active
            ? __('admin.countries.activated_toast')
            : __('admin.countries.deactivated_toast'));
    }

    public function delete(string $id): void
    {
        $country = Country::query()->whereKey($id)->first();

        if (! $country) {
            return;
        }

        $this->countries->delete($country);
        $this->toast(__('admin.countries.deleted_toast'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countries(): array
    {
        $rows = $this->countries->list()->map(function (Country $country) {
            return [
                'id' => $country->id,
                'code' => $country->code,
                'name' => $country->name,
                'image_url' => $country->imageUrl(),
                'sort_order' => $country->sort_order,
                'is_active' => $country->is_active,
            ];
        })->all();

        if ($this->statusFilter === 'active') {
            return array_values(array_filter($rows, fn (array $row) => $row['is_active']));
        }

        if ($this->statusFilter === 'inactive') {
            return array_values(array_filter($rows, fn (array $row) => ! $row['is_active']));
        }

        return $rows;
    }

    public function editingImageUrl(): ?string
    {
        if (! $this->editingId) {
            return null;
        }

        return Country::query()->whereKey($this->editingId)->first()?->imageUrl();
    }

    protected function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->formCode = '';
        $this->formName = '';
        $this->formSortOrder = '0';
        $this->formIsActive = true;
        $this->formImage = null;
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.countries', [
            'countries' => $this->countries(),
            'editingImageUrl' => $this->editingImageUrl(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.countries')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.countries');
    }
}
