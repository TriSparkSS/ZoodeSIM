<?php

namespace App\Livewire\Concerns;

use App\Models\Partner;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesAuthenticatedPartner
{
    protected function partner(): Partner
    {
        $partner = Auth::guard('partner')->user();

        if (! $partner instanceof Partner) {
            throw new HttpException(403, 'Unauthorized.');
        }

        return $partner;
    }

    protected function partnerId(): string
    {
        return (string) $this->partner()->id;
    }
}
