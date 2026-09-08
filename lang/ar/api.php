<?php

return [
    'validation_failed' => 'البيانات المقدمة غير صالحة.',
    'unauthenticated' => 'غير مصادق.',

    'user' => [
        'registered' => 'تم التسجيل بنجاح.',
        'logged_in' => 'تم تسجيل الدخول بنجاح.',
        'logged_out' => 'تم تسجيل خروجك.',
        'profile' => 'تم جلب الملف الشخصي بنجاح.',
    ],

    'promo' => [
        'invalid' => 'رمز الإحالة غير صالح.',
        'inactive' => 'رمز الإحالة هذا غير نشط.',
        'expired' => 'انتهت صلاحية رمز الإحالة هذا.',
        'exhausted' => 'وصل رمز الإحالة هذا إلى حد الاستخدام.',
        'partner_inactive' => 'رمز الإحالة هذا غير متاح.',
        'self_referral' => 'لا يمكنك استخدام رمز الإحالة الخاص بك.',
        'valid' => 'رمز الإحالة صالح.',
        'code_required' => 'يرجى إدخال رمز إحالة.',
        'device_required' => 'مطلوب معرّف جهاز لتطبيق رمز الإحالة.',
        'device_invalid' => 'معرّف الجهاز غير صالح.',
        'device_already_used' => 'استخدم هذا الجهاز مكافأة إحالة مسبقاً.',
        'ip_blocked' => 'التسجيل من هذه الشبكة غير مسموح.',
        'ip_limited' => 'تم إنشاء حسابات كثيرة من هذه الشبكة. حاول لاحقاً.',
    ],

    'notifications' => [
        'retrieved' => 'تم جلب الإشعارات بنجاح.',
        'marked_read' => 'تم تعليم الإشعار كمقروء.',
        'all_marked_read' => 'تم تعليم جميع الإشعارات كمقروءة.',
        'not_found' => 'الإشعار غير موجود.',
    ],

    'esim' => [
        'packages_retrieved' => 'تم جلب حزم eSIM بنجاح.',
        'unavailable' => 'خدمة eSIM غير متاحة مؤقتاً.',
        'invalid_country' => 'دولة غير صالحة.',
        'purchased' => 'تم شراء eSIM بنجاح.',
        'order_retrieved' => 'تم جلب طلب eSIM بنجاح.',
        'purchase_failed' => 'تعذر شراء eSIM.',
        'package_not_found' => 'الحزمة غير موجودة.',
        'package_unavailable' => 'الحزمة غير متاحة.',
        'payment_required' => 'الدفع مطلوب.',
        'provisioning_failed' => 'فشل توفير eSIM.',
        'payment_failed' => 'فشل التحقق من الدفع.',
        'already_processed' => 'تمت معالجة الطلب بالفعل.',
        'unauthorized_order' => 'وصول غير مصرح به إلى الطلب.',
        'pricing_unavailable' => 'التسعير غير متاح حالياً لهذه الحزمة.',
    ],

    'admin' => [
        'pricing' => [
            'slabs_retrieved' => 'تم جلب شرائح التسعير بنجاح.',
            'slab_created' => 'تم إنشاء شريحة التسعير بنجاح.',
            'slab_updated' => 'تم تحديث شريحة التسعير بنجاح.',
            'slab_deleted' => 'تم حذف شريحة التسعير بنجاح.',
            'preview_calculated' => 'تم حساب معاينة السعر بنجاح.',
        ],
    ],

    'validation' => [
        'name_required' => 'يرجى إدخال اسمك.',
        'phone_required' => 'يرجى إدخال رقم هاتفك.',
        'phone_unique' => 'رقم الهاتف هذا مسجل بالفعل.',
        'password_min' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.',
        'password_confirmed' => 'تأكيد كلمة المرور غير متطابق.',
    ],
];
