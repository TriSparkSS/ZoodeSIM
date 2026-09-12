<?php

return [
    'validation_failed' => 'The given data was invalid.',
    'unauthenticated' => 'Unauthenticated.',

    'user' => [
        'registered' => 'Registration successful.',
        'logged_in' => 'Login successful.',
        'logged_out' => 'You have been logged out.',
        'profile' => 'Profile retrieved successfully.',
    ],

    'wallet' => [
        'retrieved' => 'Wallet retrieved successfully.',
        'transactions_retrieved' => 'Transactions retrieved successfully.',
        'adjusted' => 'Wallet updated successfully.',
        'direction_invalid' => 'Choose whether to add or deduct funds.',
        'amount_required' => 'Please enter an amount.',
        'amount_format' => 'Enter a valid amount with up to 2 decimal places.',
        'insufficient_balance' => 'Your wallet does not have enough funds.',
    ],

    'promo' => [
        'invalid' => 'Invalid referral code.',
        'inactive' => 'This referral code is inactive.',
        'locked' => 'This referral code is locked.',
        'expired' => 'This referral code has expired.',
        'exhausted' => 'This referral code has reached its usage limit.',
        'partner_inactive' => 'This referral code is not available.',
        'self_referral' => 'You cannot use your own referral code.',
        'valid' => 'Referral code is valid.',
        'code_required' => 'Please enter a referral code.',
        'device_required' => 'A device identifier is required to apply a referral code.',
        'device_invalid' => 'The device identifier is invalid.',
        'device_already_used' => 'This device has already used a referral bonus.',
        'ip_blocked' => 'Registration from this network is not allowed.',
        'ip_limited' => 'Too many accounts were created from this network. Try again later.',
    ],

    'notifications' => [
        'retrieved' => 'Notifications retrieved successfully.',
        'marked_read' => 'Notification marked as read.',
        'all_marked_read' => 'All notifications marked as read.',
        'not_found' => 'Notification not found.',
    ],

    'esim' => [
        'packages_retrieved' => 'eSIM packages retrieved successfully.',
        'countries_retrieved' => 'Countries retrieved successfully.',
        'unavailable' => 'eSIM service temporarily unavailable.',
        'invalid_country' => 'Invalid country.',
        'purchased' => 'eSIM purchased successfully.',
        'order_retrieved' => 'eSIM order retrieved successfully.',
        'orders_retrieved' => 'eSIM orders retrieved successfully.',
        'purchase_failed' => 'Unable to purchase eSIM.',
        'package_not_found' => 'Package not found.',
        'package_unavailable' => 'Package unavailable.',
        'payment_required' => 'Payment required.',
        'payment_failed' => 'Payment verification failed.',
        'insufficient_balance' => 'Insufficient wallet balance to purchase this plan.',
        'provisioning_failed' => 'eSIM provisioning failed.',
        'already_processed' => 'Order already processed.',
        'unauthorized_order' => 'Unauthorized order access.',
        'pricing_unavailable' => 'Pricing is currently unavailable for this package.',
    ],

    'validation' => [
        'name_required' => 'Please enter your name.',
        'phone_required' => 'Please enter your phone number.',
        'phone_unique' => 'This phone number is already registered.',
        'password_min' => 'Password must be at least 8 characters.',
        'password_confirmed' => 'The password confirmation does not match.',
    ],
];
