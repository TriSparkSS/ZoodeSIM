<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '+1234567890',
            ]);
        }

        $this->call(AdminSeeder::class);
        $this->call(PartnerSeeder::class);
        $this->call(TranslatableContentSeeder::class);
        $this->call(PricingSlabSeeder::class);
        $this->call(CountrySeeder::class);
    }
}
