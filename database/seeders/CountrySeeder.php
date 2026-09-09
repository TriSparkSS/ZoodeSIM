<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * @var list<array{code: string, name: string, image_path: string, sort_order: int}>
     */
    public const DEFAULTS = [
        ['code' => 'US', 'name' => 'United States', 'image_path' => 'images/flags/us.svg', 'sort_order' => 10],
        ['code' => 'GB', 'name' => 'United Kingdom', 'image_path' => 'images/flags/gb.svg', 'sort_order' => 20],
        ['code' => 'DE', 'name' => 'Germany', 'image_path' => 'images/flags/de.svg', 'sort_order' => 30],
        ['code' => 'TR', 'name' => 'Turkey', 'image_path' => 'images/flags/tr.svg', 'sort_order' => 40],
        ['code' => 'AE', 'name' => 'United Arab Emirates', 'image_path' => 'images/flags/ae.svg', 'sort_order' => 50],
        ['code' => 'TH', 'name' => 'Thailand', 'image_path' => 'images/flags/th.svg', 'sort_order' => 60],
        ['code' => 'JP', 'name' => 'Japan', 'image_path' => 'images/flags/jp.svg', 'sort_order' => 70],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $country) {
            Country::query()->firstOrCreate(
                ['code' => $country['code']],
                [
                    'name' => $country['name'],
                    'image_path' => $country['image_path'],
                    'is_active' => true,
                    'sort_order' => $country['sort_order'],
                ],
            );
        }
    }
}
