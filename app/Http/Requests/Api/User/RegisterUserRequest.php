<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'referral_code' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('api.validation.name_required'),
            'email.required' => __('auth.validation.email_required'),
            'email.email' => __('auth.validation.email_invalid'),
            'phone.required' => __('api.validation.phone_required'),
            'phone.unique' => __('api.validation.phone_unique'),
            'password.required' => __('auth.validation.password_required'),
            'password.min' => __('api.validation.password_min'),
            'password.confirmed' => __('api.validation.password_confirmed'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('referral_code') && is_string($this->referral_code) && trim($this->referral_code) === '') {
            $this->merge(['referral_code' => null]);
        }
    }
}
