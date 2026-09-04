<?php

namespace App\Services\Partner;

use App\Models\Partner;
use App\Models\PartnerApplication;
use Illuminate\Support\Str;

class PartnerService
{
    /**
     * @return array{partner: Partner, plain_password: string}
     */
    public function createFromApplication(PartnerApplication $application, ?string $password = null): array
    {
        $plainPassword = $password ?? Str::password(12);

        $partner = Partner::query()->create([
            'name' => trim($application->first_name.' '.($application->last_name ?? '')),
            'email' => $application->email,
            'password' => $plainPassword,
            'social_contacts' => [
                'telegram' => $application->telegram,
                'instagram' => $application->instagram,
                'twitter' => null,
            ],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        return [
            'partner' => $partner,
            'plain_password' => $plainPassword,
        ];
    }
}
