<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+1##########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'auth_provider' => User::AUTH_PASSWORD,
            'firebase_uid' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function social(string $provider = User::AUTH_GOOGLE): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
            'password' => null,
            'firebase_uid' => (string) Str::uuid(),
            'auth_provider' => $provider,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
