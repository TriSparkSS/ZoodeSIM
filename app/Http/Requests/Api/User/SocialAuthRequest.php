<?php

namespace App\Http\Requests\Api\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialAuthRequest extends FormRequest
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
            'id_token' => ['required', 'string'],
            'provider' => ['required', 'string', Rule::in([User::AUTH_GOOGLE, User::AUTH_APPLE])],
            'name' => ['nullable', 'string', 'max:255'],
            'referral_code' => ['nullable', 'string', 'max:32'],
            'device_id' => ['nullable', 'required_with:referral_code', 'string', 'min:8', 'max:128', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_token.required' => __('api.user.social_token_required'),
            'provider.required' => __('api.user.social_provider_invalid'),
            'provider.in' => __('api.user.social_provider_invalid'),
            'device_id.required_with' => __('api.promo.device_required'),
            'device_id.regex' => __('api.promo.device_invalid'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('referral_code') && is_string($this->referral_code) && trim($this->referral_code) === '') {
            $merge['referral_code'] = null;
        }

        if ($this->has('name') && is_string($this->name) && trim($this->name) === '') {
            $merge['name'] = null;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
