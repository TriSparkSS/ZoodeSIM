<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class AdjustUserWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'direction' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'direction.required' => __('api.wallet.direction_invalid'),
            'direction.in' => __('api.wallet.direction_invalid'),
            'amount.required' => __('api.wallet.amount_required'),
            'amount.regex' => __('api.wallet.amount_format'),
        ];
    }
}
