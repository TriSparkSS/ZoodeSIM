<?php

namespace App\Http\Requests\Api\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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
            'email.unique' => __('api.validation.email_unique'),
            'phone.required' => __('api.validation.phone_required'),
            'phone.unique' => __('api.validation.phone_unique'),
            'current_password.required_with' => __('api.validation.current_password_required'),
            'password.min' => __('api.validation.password_min'),
            'password.confirmed' => __('api.validation.password_confirmed'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('password') && is_string($this->password) && trim($this->password) === '') {
            $merge['password'] = null;
        }

        if ($this->has('current_password') && is_string($this->current_password) && trim($this->current_password) === '') {
            $merge['current_password'] = null;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
