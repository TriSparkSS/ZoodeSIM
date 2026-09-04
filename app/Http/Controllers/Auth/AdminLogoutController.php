<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\AuthActivityLogger;
use App\Services\Auth\AuthSessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLogoutController
{
    public function __invoke(
        Request $request,
        AuthActivityLogger $logger,
        AuthSessionManager $sessions,
    ): RedirectResponse {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            $logger->logout('admin', $admin);
            $sessions->revokeCurrent($admin);
        }

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
