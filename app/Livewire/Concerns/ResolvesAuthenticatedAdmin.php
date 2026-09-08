<?php

namespace App\Livewire\Concerns;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

trait ResolvesAuthenticatedAdmin
{
    protected function admin(): Admin
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin instanceof Admin) {
            throw new AuthorizationException('Unauthorized.');
        }

        return $admin;
    }
}
