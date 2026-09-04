<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\AuthActivityLogger;
use App\Services\Auth\AuthSessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartnerLogoutController
{
    public function __invoke(
        Request $request,
        AuthActivityLogger $logger,
        AuthSessionManager $sessions,
    ): RedirectResponse {
        $partner = Auth::guard('partner')->user();

        if ($partner) {
            $logger->logout('partner', $partner);
            $sessions->revokeCurrent($partner);
        }

        Auth::guard('partner')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
