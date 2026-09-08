<?php

return [
    'referral' => [
        'subject' => 'New referral registered',
        'title' => 'New referral',
        'body' => ':name registered with your code. You earned $:amount.',
    ],
    'purchase' => [
        'subject' => 'Purchase commission credited',
        'title' => 'Purchase commission',
        'body' => ':name bought an eSIM. You earned :amount.',
    ],
    'milestone' => [
        'subject' => 'Referral milestone: :count',
        'title' => ':count referrals reached',
        'body' => 'You reached :count referrals and earned :amount.',
    ],
    'payout' => [
        'subject' => 'Payout update',
        'title' => 'Payout update',
        'body' => 'Your :amount payout is now :status.',
    ],
    'expiry' => [
        'subject' => 'Promo :code expires soon',
        'title' => 'Promo expiring soon',
        'body' => 'Promo :code expires on :date.',
    ],
    'cashback' => [
        'subject' => 'Cashback credited',
        'title' => 'Purchase cashback',
        'body' => 'You received :amount cashback on your eSIM purchase.',
    ],
    'bonus' => [
        'subject' => 'Referral bonus added',
        'title' => 'Referral bonus',
        'body' => 'You received :bonus MB from a referral code.',
    ],
];
