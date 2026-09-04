<?php

namespace App\Http\Controllers;

use App\Services\Locale\LocaleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, LocaleManager $locales, string $locale): RedirectResponse
    {
        $locales->set($locale);

        $fallback = url('/');
        $previous = url()->previous();

        return redirect()->to($previous !== $request->fullUrl() ? $previous : $fallback);
    }
}
