<?php

namespace App\Http\Requests\Api\User;

use App\Models\EsimOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexEsimOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', Rule::in(EsimOrder::statuses())],
            'payment_status' => ['nullable', 'string', Rule::in(EsimOrder::paymentStatuses())],
            'package_code' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:8'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function filters(): array
    {
        return collect($this->validated())
            ->only(['client_id', 'status', 'payment_status', 'package_code', 'location', 'date_from', 'date_to'])
            ->filter(fn (mixed $value) => filled($value))
            ->map(fn (mixed $value) => (string) $value)
            ->all();
    }
}
