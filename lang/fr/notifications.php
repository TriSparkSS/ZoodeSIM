<?php

return [
    'referral' => [
        'subject' => 'Nouveau filleul inscrit',
        'title' => 'Nouveau parrainage',
        'body' => ':name s’est inscrit avec votre code. Vous avez gagné $:amount.',
    ],
    'purchase' => [
        'subject' => 'Commission d’achat créditée',
        'title' => 'Commission d’achat',
        'body' => ':name a acheté une eSIM. Vous avez gagné :amount.',
    ],
    'milestone' => [
        'subject' => 'Palier de parrainage : :count',
        'title' => ':count filleuls atteints',
        'body' => 'Vous avez atteint :count filleuls et gagné :amount.',
    ],
    'payout' => [
        'subject' => 'Mise à jour de paiement',
        'title' => 'Mise à jour de paiement',
        'body' => 'Votre paiement de :amount est maintenant :status.',
    ],
    'expiry' => [
        'subject' => 'Le code :code expire bientôt',
        'title' => 'Code bientôt expiré',
        'body' => 'Le code :code expire le :date.',
    ],
    'cashback' => [
        'subject' => 'Cashback crédité',
        'title' => 'Cashback d’achat',
        'body' => 'Vous avez reçu :amount de cashback sur votre achat eSIM.',
    ],
    'bonus' => [
        'subject' => 'Bonus de parrainage ajouté',
        'title' => 'Bonus de parrainage',
        'body' => 'Vous avez reçu :bonus Mo via un code de parrainage.',
    ],
];
