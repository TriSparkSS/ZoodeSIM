<?php

return [
    'referral' => [
        'subject' => 'Neue Empfehlung registriert',
        'title' => 'Neue Empfehlung',
        'body' => ':name hat sich mit deinem Code registriert. Du hast $:amount verdient.',
    ],
    'purchase' => [
        'subject' => 'Kaufprovision gutgeschrieben',
        'title' => 'Kaufprovision',
        'body' => ':name hat eine eSIM gekauft. Du hast :amount verdient.',
    ],
    'milestone' => [
        'subject' => 'Empfehlungsmeilenstein: :count',
        'title' => ':count Empfehlungen erreicht',
        'body' => 'Du hast :count Empfehlungen erreicht und :amount verdient.',
    ],
    'payout' => [
        'subject' => 'Auszahlungsupdate',
        'title' => 'Auszahlungsupdate',
        'body' => 'Deine Auszahlung von :amount ist jetzt :status.',
    ],
    'expiry' => [
        'subject' => 'Promo :code läuft bald ab',
        'title' => 'Promo läuft bald ab',
        'body' => 'Promo :code läuft am :date ab.',
    ],
    'cashback' => [
        'subject' => 'Cashback gutgeschrieben',
        'title' => 'Kauf-Cashback',
        'body' => 'Du hast :amount Cashback für deinen eSIM-Kauf erhalten.',
    ],
    'bonus' => [
        'subject' => 'Empfehlungsbonus gutgeschrieben',
        'title' => 'Empfehlungsbonus',
        'body' => 'Du hast :bonus MB über einen Empfehlungscode erhalten.',
    ],
];
