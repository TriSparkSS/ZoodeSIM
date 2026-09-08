<?php

namespace App\Livewire\Admin;

use App\Exceptions\PricingUnavailableException;
use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\PricingSlab;
use App\Services\Pricing\PricingSlabService;
use App\Support\Money;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PricingSlabs extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public string $statusFilter = 'all';

    public bool $showFormModal = false;

    public ?string $editingId = null;

    public string $formMinAmount = '';

    public string $formMaxAmount = '';

    public string $formPercentage = '';

    public string $formPriority = '100';

    public bool $formIsActive = true;

    public string $previewCost = '50.00';

    /**
     * @var array<string, string>|null
     */
    public ?array $previewResult = null;

    public ?string $previewError = null;

    protected PricingSlabService $slabs;

    public function boot(PricingSlabService $slabs): void
    {
        $this->slabs = $slabs;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(string $id): void
    {
        $slab = PricingSlab::query()->whereKey($id)->first();

        if (! $slab) {
            return;
        }

        $this->resetValidation();
        $this->editingId = $slab->id;
        $this->formMinAmount = (string) $slab->min_amount;
        $this->formMaxAmount = (string) $slab->max_amount;
        $this->formPercentage = (string) $slab->percentage;
        $this->formPriority = (string) $slab->priority;
        $this->formIsActive = $slab->is_active;
        $this->showFormModal = true;
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'formMinAmount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'formMaxAmount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'formPercentage' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'formPriority' => ['required', 'integer', 'min:0', 'max:9999'],
            'formIsActive' => ['boolean'],
        ], [
            'formMinAmount.required' => __('admin.pricing_slabs.validation.min_required'),
            'formMinAmount.regex' => __('admin.pricing_slabs.validation.amount_format'),
            'formMaxAmount.required' => __('admin.pricing_slabs.validation.max_required'),
            'formMaxAmount.regex' => __('admin.pricing_slabs.validation.amount_format'),
            'formPercentage.required' => __('admin.pricing_slabs.validation.percentage_required'),
            'formPercentage.min' => __('admin.pricing_slabs.validation.percentage_range'),
            'formPercentage.max' => __('admin.pricing_slabs.validation.percentage_range'),
            'formPercentage.regex' => __('admin.pricing_slabs.validation.percentage_format'),
            'formPriority.required' => __('admin.pricing_slabs.validation.priority_required'),
            'formPriority.integer' => __('admin.pricing_slabs.validation.priority_invalid'),
        ]);

        $payload = [
            'min_amount' => $validated['formMinAmount'],
            'max_amount' => $validated['formMaxAmount'],
            'percentage' => $validated['formPercentage'],
            'priority' => (int) $validated['formPriority'],
            'is_active' => (bool) $validated['formIsActive'],
        ];

        try {
            if ($this->editingId) {
                $slab = PricingSlab::query()->whereKey($this->editingId)->first();

                if (! $slab) {
                    return;
                }

                $this->slabs->update($slab, $payload, auth('admin')->user());
                $this->toast(__('admin.pricing_slabs.updated_toast'));
            } else {
                $this->slabs->create($payload, auth('admin')->user());
                $this->toast(__('admin.pricing_slabs.created_toast'));
            }
        } catch (ValidationException $e) {
            $this->toast(collect($e->errors())->flatten()->first() ?: __('admin.pricing_slabs.validation.overlap'), 'error');

            return;
        }

        $this->closeModal();
        $this->clearPreview();
    }

    public function toggle(string $id): void
    {
        $slab = PricingSlab::query()->whereKey($id)->first();

        if (! $slab) {
            return;
        }

        try {
            $updated = $this->slabs->toggle($slab, auth('admin')->user());
        } catch (ValidationException $e) {
            $this->toast(collect($e->errors())->flatten()->first() ?: __('admin.pricing_slabs.validation.overlap'), 'error');

            return;
        }

        $this->toast($updated->is_active
            ? __('admin.pricing_slabs.activated_toast')
            : __('admin.pricing_slabs.deactivated_toast'));
        $this->clearPreview();
    }

    public function delete(string $id): void
    {
        $slab = PricingSlab::query()->whereKey($id)->first();

        if (! $slab) {
            return;
        }

        $this->slabs->delete($slab, auth('admin')->user());
        $this->toast(__('admin.pricing_slabs.deleted_toast'));
        $this->clearPreview();
    }

    public function preview(): void
    {
        $this->validate([
            'previewCost' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
        ], [
            'previewCost.required' => __('admin.pricing_slabs.validation.preview_cost_required'),
            'previewCost.regex' => __('admin.pricing_slabs.validation.amount_format'),
        ]);

        try {
            $quote = $this->slabs->preview($this->previewCost);
        } catch (PricingUnavailableException) {
            $this->previewResult = null;
            $this->previewError = __('api.esim.pricing_unavailable');

            return;
        }

        $this->previewError = null;
        $this->previewResult = [
            'provider_cost' => $quote->providerCost->format(),
            'markup_percentage' => $quote->markupPercentage.'%',
            'markup_amount' => $quote->markupAmount->format(),
            'customer_price' => $quote->customerPrice->format(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function slabs(): array
    {
        $currency = (string) config('pricing.currency', 'USD');

        $slabs = $this->slabs->list()->map(function (PricingSlab $slab) use ($currency) {
            return [
                'id' => $slab->id,
                'min' => Money::fromDecimal((string) $slab->min_amount, $currency)->format(),
                'max' => Money::fromDecimal((string) $slab->max_amount, $currency)->format(),
                'percentage' => rtrim(rtrim((string) $slab->percentage, '0'), '.').'%',
                'priority' => $slab->priority,
                'is_active' => $slab->is_active,
            ];
        })->all();

        if ($this->statusFilter === 'active') {
            return array_values(array_filter($slabs, fn (array $slab) => $slab['is_active']));
        }

        if ($this->statusFilter === 'inactive') {
            return array_values(array_filter($slabs, fn (array $slab) => ! $slab['is_active']));
        }

        return $slabs;
    }

    protected function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->formMinAmount = '';
        $this->formMaxAmount = '';
        $this->formPercentage = '';
        $this->formPriority = '100';
        $this->formIsActive = true;
    }

    protected function clearPreview(): void
    {
        $this->previewResult = null;
        $this->previewError = null;
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.pricing-slabs', [
            'slabs' => $this->slabs(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.pricing_slabs')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.pricing_slabs');
    }
}
