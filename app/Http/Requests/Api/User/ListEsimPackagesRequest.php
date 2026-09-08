<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class ListEsimPackagesRequest extends FormRequest
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
            'country' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'country.size' => __('api.esim.invalid_country'),
            'country.regex' => __('api.esim.invalid_country'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $country = $this->query('country');

        if (is_string($country) && trim($country) === '') {
            $this->merge(['country' => null]);

            return;
        }

        if (is_string($country)) {
            $this->merge(['country' => strtoupper(trim($country))]);
        }
    }
}
