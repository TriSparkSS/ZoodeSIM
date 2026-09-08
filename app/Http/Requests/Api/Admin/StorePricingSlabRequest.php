<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePricingSlabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'min_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'max_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'priority' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'min_amount.required' => __('admin.pricing_slabs.validation.min_required'),
            'min_amount.regex' => __('admin.pricing_slabs.validation.amount_format'),
            'max_amount.required' => __('admin.pricing_slabs.validation.max_required'),
            'max_amount.regex' => __('admin.pricing_slabs.validation.amount_format'),
            'percentage.required' => __('admin.pricing_slabs.validation.percentage_required'),
            'percentage.min' => __('admin.pricing_slabs.validation.percentage_range'),
            'percentage.max' => __('admin.pricing_slabs.validation.percentage_range'),
            'percentage.regex' => __('admin.pricing_slabs.validation.percentage_format'),
            'priority.required' => __('admin.pricing_slabs.validation.priority_required'),
            'priority.integer' => __('admin.pricing_slabs.validation.priority_invalid'),
            'priority.min' => __('admin.pricing_slabs.validation.priority_invalid'),
            'priority.max' => __('admin.pricing_slabs.validation.priority_invalid'),
        ];
    }

    /**
     * @return array{min_amount: string, max_amount: string, percentage: string, priority: int, is_active: bool}
     */
    public function payload(): array
    {
        return [
            'min_amount' => (string) $this->validated('min_amount'),
            'max_amount' => (string) $this->validated('max_amount'),
            'percentage' => (string) $this->validated('percentage'),
            'priority' => (int) $this->validated('priority'),
            'is_active' => $this->boolean('is_active', true),
        ];
    }
}
