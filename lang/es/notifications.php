<?php

return [
    'referral' => [
        'subject' => 'Nuevo referido registrado',
        'title' => 'Nuevo referido',
        'body' => ':name se registró con tu código. Ganaste $:amount.',
    ],
    'purchase' => [
        'subject' => 'Comisión de compra acreditada',
        'title' => 'Comisión de compra',
        'body' => ':name compró una eSIM. Ganaste :amount.',
    ],
    'milestone' => [
        'subject' => 'Hito de referidos: :count',
        'title' => ':count referidos alcanzados',
        'body' => 'Alcanzaste :count referidos y ganaste :amount.',
    ],
    'payout' => [
        'subject' => 'Actualización de pago',
        'title' => 'Actualización de pago',
        'body' => 'Tu pago de :amount ahora está :status.',
    ],
    'expiry' => [
        'subject' => 'El promo :code caduca pronto',
        'title' => 'Promo por caducar',
        'body' => 'El promo :code caduca el :date.',
    ],
    'unlock' => [
        'subject' => 'El promo :code ya está desbloqueado',
        'title' => 'Promo desbloqueado',
        'body' => 'El promo :code se desbloqueó tras :count referidos y ahora es tu código activo.',
    ],
    'cashback' => [
        'subject' => 'Cashback acreditado',
        'title' => 'Cashback de compra',
        'body' => 'Recibiste :amount de cashback por tu compra de eSIM.',
    ],
    'bonus' => [
        'subject' => 'Bono de referido añadido',
        'title' => 'Bono de referido',
        'body' => 'Recibiste :bonus MB de un código de referido.',
        'body_usd' => 'Recibiste $:bonus de un código de referido.',
    ],
];
