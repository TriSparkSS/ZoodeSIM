<?php

return [
    'referral' => [
        'subject' => 'تم تسجيل إحالة جديدة',
        'title' => 'إحالة جديدة',
        'body' => 'سجّل :name برمزك. ربحت $:amount.',
    ],
    'purchase' => [
        'subject' => 'تم إضافة عمولة الشراء',
        'title' => 'عمولة الشراء',
        'body' => 'اشترى :name شريحة eSIM. ربحت :amount.',
    ],
    'milestone' => [
        'subject' => 'مكافأة الإحالات: :count',
        'title' => 'وصلت إلى :count إحالات',
        'body' => 'وصلت إلى :count إحالات وربحت :amount.',
    ],
    'payout' => [
        'subject' => 'تحديث الدفع',
        'title' => 'تحديث الدفع',
        'body' => 'دفعتك بقيمة :amount أصبحت الآن :status.',
    ],
    'expiry' => [
        'subject' => 'الرمز :code ينتهي قريباً',
        'title' => 'ينتهي الرمز قريباً',
        'body' => 'ينتهي الرمز :code في :date.',
    ],
    'unlock' => [
        'subject' => 'تم فتح الرمز :code',
        'title' => 'تم فتح الرمز',
        'body' => 'تم فتح الرمز :code بعد :count إحالة وهو الآن رمزك النشط.',
    ],
    'cashback' => [
        'subject' => 'تم إضافة الاسترداد',
        'title' => 'استرداد الشراء',
        'body' => 'حصلت على :amount استرداداً من شراء eSIM.',
    ],
    'bonus' => [
        'subject' => 'تمت إضافة مكافأة الإحالة',
        'title' => 'مكافأة الإحالة',
        'body' => 'حصلت على :bonus ميجابايت من رمز إحالة.',
        'body_usd' => 'حصلت على $:bonus من رمز إحالة.',
    ],
];
