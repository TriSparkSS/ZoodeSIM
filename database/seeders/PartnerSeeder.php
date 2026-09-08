<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        Partner::query()->updateOrCreate(
            ['email' => 'partner@zoodesim.test'],
            [
                'name' => 'Demo Partner',
                'password' => '11223344',
                'social_contacts' => [
                    'telegram' => null,
                    'instagram' => null,
                    'twitter' => null,
                ],
                'status' => 'active',
                'balance' => 0,
                'total_earned' => 0,
            ],
        );
    }
}
