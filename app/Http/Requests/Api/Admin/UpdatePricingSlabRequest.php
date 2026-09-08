<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingSlabRequest extends FormRequest
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
            'min_amount' => ['sometimes', 'regex:/^\d+(\.\d{1,2})?$/'],
            'max_amount' => ['sometimes', 'regex:/^\d+(\.\d{1,2})?$/'],
            'percentage' => ['sometimes', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'min_amount.regex' => __('admin.pricing_slabs.validation.amount_format'),
            'max_amount.regex' => __('admin.pricing_slabs.validation.amount_format'),
            'percentage.min' => __('admin.pricing_slabs.validation.percentage_range'),
            'percentage.max' => __('admin.pricing_slabs.validation.percentage_range'),
            'percentage.regex' => __('admin.pricing_slabs.validation.percentage_format'),
            'priority.integer' => __('admin.pricing_slabs.validation.priority_invalid'),
            'priority.min' => __('admin.pricing_slabs.validation.priority_invalid'),
            'priority.max' => __('admin.pricing_slabs.validation.priority_invalid'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $payload = [];

        foreach (['min_amount', 'max_amount', 'percentage'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = (string) $validated[$field];
            }
        }

        if (array_key_exists('priority', $validated)) {
            $payload['priority'] = (int) $validated['priority'];
        }

        if (array_key_exists('is_active', $validated)) {
            $payload['is_active'] = $this->boolean('is_active');
        }

        return $payload;
    }
}
