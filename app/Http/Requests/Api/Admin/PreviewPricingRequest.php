<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PreviewPricingRequest extends FormRequest
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
            'provider_cost' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider_cost.required' => __('admin.pricing_slabs.validation.preview_cost_required'),
            'provider_cost.regex' => __('admin.pricing_slabs.validation.amount_format'),
        ];
    }
}
