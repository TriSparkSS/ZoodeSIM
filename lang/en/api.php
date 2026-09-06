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

    'promo' => [
        'invalid' => 'Invalid referral code.',
        'inactive' => 'This referral code is inactive.',
        'expired' => 'This referral code has expired.',
        'exhausted' => 'This referral code has reached its usage limit.',
        'partner_inactive' => 'This referral code is not available.',
        'self_referral' => 'You cannot use your own referral code.',
    ],

    'validation' => [
        'name_required' => 'Please enter your name.',
        'phone_required' => 'Please enter your phone number.',
        'phone_unique' => 'This phone number is already registered.',
        'password_min' => 'Password must be at least 8 characters.',
        'password_confirmed' => 'The password confirmation does not match.',
    ],
];
