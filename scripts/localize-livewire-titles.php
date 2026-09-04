<?php

$root = dirname(__DIR__);

$map = [
    'app/Livewire/Admin/Applications.php' => 'admin.nav.applications',
    'app/Livewire/Admin/Partners.php' => 'admin.nav.partners',
    'app/Livewire/Admin/PromoCodes.php' => 'admin.nav.promo_codes',
    'app/Livewire/Admin/Payouts.php' => 'admin.nav.payouts',
    'app/Livewire/Admin/Statistics.php' => 'admin.nav.statistics',
    'app/Livewire/Admin/Profile.php' => 'admin.profile.title',
    'app/Livewire/Partner/Dashboard.php' => 'partner.dashboard.title',
    'app/Livewire/Partner/Registrations.php' => 'partner.nav.registrations',
    'app/Livewire/Partner/Earnings.php' => 'partner.nav.earnings',
    'app/Livewire/Partner/PromoCodes.php' => 'partner.nav.promo_codes',
    'app/Livewire/Partner/Statistics.php' => 'partner.nav.statistics',
    'app/Livewire/Partner/Settings.php' => 'partner.nav.settings',
    'app/Livewire/Auth/AdminLogin.php' => 'auth.admin.title',
    'app/Livewire/Auth/PartnerLogin.php' => 'auth.partner.title',
    'app/Livewire/Public/ApplyForm.php' => 'apply.form.title',
];

foreach ($map as $relative => $titleKey) {
    $file = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $c = file_get_contents($file);

    $c = preg_replace('/use Livewire\\\\Attributes\\\\Title;\r?\n/', '', $c);
    $c = preg_replace('/#\[Title\([^\]]*\)\]\r?\n/', '', $c);

    if (! str_contains($c, 'WithLocalizedTitle')) {
        if (str_contains($c, 'use WithToast;')) {
            $c = str_replace(
                'use WithToast;',
                "use WithLocalizedTitle;\n    use WithToast;",
                $c
            );
        } elseif (preg_match('/use WithAdminNavigation;/', $c)) {
            $c = str_replace(
                'use WithAdminNavigation;',
                "use WithAdminNavigation;\n    use WithLocalizedTitle;",
                $c
            );
        } elseif (preg_match('/use WithPartnerNavigation;/', $c)) {
            $c = str_replace(
                'use WithPartnerNavigation;',
                "use WithPartnerNavigation;\n    use WithLocalizedTitle;",
                $c
            );
        } else {
            // Insert trait after class opening
            $c = preg_replace(
                '/(class \w+ extends Component\s*\{)/',
                "$1\n    use \\App\\Livewire\\Concerns\\WithLocalizedTitle;",
                $c,
                1
            );
        }

        if (! str_contains($c, 'use App\\Livewire\\Concerns\\WithLocalizedTitle;')
            && ! str_contains($c, 'use \\App\\Livewire\\Concerns\\WithLocalizedTitle')) {
            $c = preg_replace(
                '/(namespace App\\\\Livewire\\\\[^;]+;\r?\n)/',
                "$1\nuse App\\Livewire\\Concerns\\WithLocalizedTitle;\n",
                $c,
                1
            );
        }
    }

    // Wrap return view(...)->layout(...) with title if layout exists
    if (preg_match('/return view\((.*?)\)->layout\((.*?)\);/s', $c, $m)) {
        $c = preg_replace(
            '/return view\((.*?)\)->layout\((.*?)\);/s',
            'return $this->withLocalizedTitle(view($1)->layout($2), '.var_export($titleKey, true).');',
            $c,
            1
        );
    } elseif (preg_match('/return view\(([^;]+)\);/', $c)) {
        $c = preg_replace(
            '/return view\(([^;]+)\);/',
            'return $this->withLocalizedTitle(view($1), '.var_export($titleKey, true).');',
            $c,
            1
        );
    }

    file_put_contents($file, $c);
    echo "Updated {$relative}\n";
}

echo "Done\n";
