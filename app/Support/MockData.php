<?php

namespace App\Support;

class MockData
{
    public static function partnerStats(): array
    {
        return [
            'registrations' => 247,
            'registrations_change' => '+23',
            'earnings' => 370.00,
            'earnings_change' => '+$34.50',
            'active_users' => 189,
            'conversion' => '76%',
            'available_withdrawal' => 142.00,
            'promo_code' => 'SARDOR10',
            'promo_bonus' => '200 MB',
            'promo_reward' => '$1.50',
        ];
    }

    public static function recentRegistrations(): array
    {
        return [
            ['initial' => 'A', 'name' => 'Alisher K.', 'time' => 'today_1432', 'code' => 'SARDOR10', 'bonus' => '200 MB', 'gradient' => 'from-brand-cyan to-brand-purple'],
            ['initial' => 'M', 'name' => 'Madina T.', 'time' => 'today_1115', 'code' => 'SARDOR10', 'bonus' => '200 MB', 'gradient' => 'from-brand-green to-brand-cyan'],
            ['initial' => 'D', 'name' => 'Davlat R.', 'time' => 'yesterday_2044', 'code' => 'SARDOR10', 'bonus' => '200 MB', 'gradient' => 'from-brand-purple to-brand-red'],
            ['initial' => 'N', 'name' => 'Nilufar S.', 'time' => 'yesterday_1708', 'code' => 'SARDOR10', 'bonus' => '200 MB', 'gradient' => 'from-brand-yellow to-brand-red'],
        ];
    }

    public static function partnerPromoCodes(): array
    {
        return [
            ['code' => 'SARDOR10', 'uses' => 247, 'bonus' => '200 MB', 'earnings' => 370.50, 'status' => 'active'],
            ['code' => 'SARDOR5', 'uses' => 89, 'bonus' => '100 MB', 'earnings' => 133.50, 'status' => 'active'],
            ['code' => 'SUMMER26', 'uses' => 12, 'bonus' => '500 MB', 'earnings' => 18.00, 'status' => 'pending'],
        ];
    }

    public static function chartData(): array
    {
        return [
            ['label' => 'mon', 'value' => 40],
            ['label' => 'tue', 'value' => 60],
            ['label' => 'wed', 'value' => 45],
            ['label' => 'thu', 'value' => 80],
            ['label' => 'fri', 'value' => 65],
            ['label' => 'sat', 'value' => 90],
            ['label' => 'sun', 'value' => 55],
        ];
    }

    public static function applications(): array
    {
        return [
            ['id' => 1, 'name' => 'Sardor Rahimov', 'email' => 'sardor@gmail.com', 'phone' => '+992901234567', 'platforms' => ['instagram', 'telegram'], 'followers' => '50k-100k', 'niche' => 'travel', 'country' => 'tajikistan', 'about' => 'Travel blogger, 7 years experience', 'status' => 'pending', 'date' => '2026-07-12', 'gradient' => 'from-brand-cyan to-brand-purple'],
            ['id' => 2, 'name' => 'Malika Tosheva', 'email' => 'malika@mail.ru', 'phone' => '+998901234567', 'platforms' => ['tiktok', 'instagram'], 'followers' => '100k-500k', 'niche' => 'lifestyle', 'country' => 'uzbekistan', 'about' => 'TikTok blogger with active audience', 'status' => 'pending', 'date' => '2026-07-11', 'gradient' => 'from-brand-green to-brand-cyan'],
            ['id' => 3, 'name' => 'Jahon Umarov', 'email' => 'jahon@gmail.com', 'phone' => '+992771234567', 'platforms' => ['youtube', 'telegram'], 'followers' => '10k-50k', 'niche' => 'tech', 'country' => 'tajikistan', 'about' => 'Tech reviews in Tajik', 'status' => 'pending', 'date' => '2026-07-10', 'gradient' => 'from-brand-purple to-brand-red'],
            ['id' => 4, 'name' => 'Nilufar Saidova', 'email' => 'nilufar@gmail.com', 'phone' => '+992881234567', 'platforms' => ['instagram'], 'followers' => '1k-10k', 'niche' => 'lifestyle', 'country' => 'tajikistan', 'about' => '', 'status' => 'approved', 'date' => '2026-07-09', 'gradient' => 'from-brand-yellow to-brand-red', 'promo' => 'NILUFAR10'],
            ['id' => 5, 'name' => 'Davlat Nazarov', 'email' => 'davlat@mail.ru', 'phone' => '+992901112233', 'platforms' => ['telegram'], 'followers' => '50k-100k', 'niche' => 'education', 'country' => 'tajikistan', 'about' => 'Educational channel', 'status' => 'approved', 'date' => '2026-07-08', 'gradient' => 'from-brand-cyan to-brand-green', 'promo' => 'DAVLAT20'],
            ['id' => 6, 'name' => 'Farrukh Aliev', 'email' => 'farrukh@gmail.com', 'phone' => '+998901112233', 'platforms' => ['tiktok'], 'followers' => '1k-10k', 'niche' => 'entertainment', 'country' => 'uzbekistan', 'about' => '', 'status' => 'rejected', 'date' => '2026-07-07', 'gradient' => 'from-brand-red to-brand-purple'],
            ['id' => 7, 'name' => 'Zulfiya Karimova', 'email' => 'zulfiya@gmail.com', 'phone' => '+992901234000', 'platforms' => ['instagram', 'tiktok'], 'followers' => '10k-50k', 'niche' => 'travel', 'country' => 'tajikistan', 'about' => 'Traveling the world', 'status' => 'pending', 'date' => '2026-07-12', 'gradient' => 'from-brand-green to-brand-purple'],
            ['id' => 8, 'name' => 'Bahodir Tursunov', 'email' => 'bahodir@mail.ru', 'phone' => '+998991234567', 'platforms' => ['youtube'], 'followers' => '500k-1m', 'niche' => 'tech', 'country' => 'uzbekistan', 'about' => 'Large YouTube channel', 'status' => 'pending', 'date' => '2026-07-11', 'gradient' => 'from-brand-cyan to-brand-yellow'],
        ];
    }

    public static function partners(): array
    {
        return [
            ['id' => 1, 'name' => 'Sardor Rahimov', 'email' => 'sardor@gmail.com', 'level' => 2, 'registrations' => 247, 'earnings' => 370.50, 'promo' => 'SARDOR10', 'status' => 'active'],
            ['id' => 2, 'name' => 'Nilufar Saidova', 'email' => 'nilufar@gmail.com', 'level' => 1, 'registrations' => 89, 'earnings' => 133.50, 'promo' => 'NILUFAR10', 'status' => 'active'],
            ['id' => 3, 'name' => 'Davlat Nazarov', 'email' => 'davlat@mail.ru', 'level' => 2, 'registrations' => 156, 'earnings' => 234.00, 'promo' => 'DAVLAT20', 'status' => 'active'],
            ['id' => 4, 'name' => 'Malika Tosheva', 'email' => 'malika@mail.ru', 'level' => 1, 'registrations' => 0, 'earnings' => 0, 'promo' => null, 'status' => 'pending'],
        ];
    }

    public static function payouts(): array
    {
        return [
            ['id' => 1, 'partner' => 'Sardor Rahimov', 'amount' => 142.00, 'method' => 'PayPal', 'status' => 'pending', 'date' => '2026-07-12'],
            ['id' => 2, 'partner' => 'Nilufar Saidova', 'amount' => 85.50, 'method' => 'Bank Transfer', 'status' => 'approved', 'date' => '2026-07-10'],
            ['id' => 3, 'partner' => 'Davlat Nazarov', 'amount' => 200.00, 'method' => 'PayPal', 'status' => 'completed', 'date' => '2026-07-05'],
            ['id' => 4, 'partner' => 'Sardor Rahimov', 'amount' => 228.00, 'method' => 'Bank Transfer', 'status' => 'completed', 'date' => '2026-06-28'],
        ];
    }

    public static function allRegistrations(): array
    {
        return array_merge(self::recentRegistrations(), [
            ['initial' => 'S', 'name' => 'Shahlo M.', 'time' => 'yesterday_1200', 'code' => 'SARDOR10', 'bonus' => '200 MB', 'gradient' => 'from-brand-cyan to-brand-green'],
            ['initial' => 'F', 'name' => 'Farrukh A.', 'time' => 'yesterday_0900', 'code' => 'SARDOR5', 'bonus' => '100 MB', 'gradient' => 'from-brand-purple to-brand-cyan'],
            ['initial' => 'Z', 'name' => 'Zarina K.', 'time' => 'two_days_ago', 'code' => 'SARDOR10', 'bonus' => '200 MB', 'gradient' => 'from-brand-yellow to-brand-purple'],
        ]);
    }

    public static function partnerEarningsHistory(): array
    {
        return [
            ['type' => 'registration', 'description' => 'Alisher K.', 'amount' => 1.50, 'date' => '2026-07-12'],
            ['type' => 'registration', 'description' => 'Madina T.', 'amount' => 1.50, 'date' => '2026-07-12'],
            ['type' => 'purchase', 'description' => 'Davlat R.', 'amount' => 4.50, 'date' => '2026-07-11'],
            ['type' => 'registration', 'description' => 'Nilufar S.', 'amount' => 1.50, 'date' => '2026-07-11'],
            ['type' => 'purchase', 'description' => 'Shahlo M.', 'amount' => 2.90, 'date' => '2026-07-10'],
            ['type' => 'registration', 'description' => 'Farrukh A.', 'amount' => 1.50, 'date' => '2026-07-09'],
        ];
    }

    public static function partnerWithdrawals(): array
    {
        return array_values(array_filter(
            self::payouts(),
            fn (array $payout) => $payout['partner'] === 'Sardor Rahimov'
        ));
    }

    public static function monthlyTrendData(): array
    {
        return [
            ['month' => 2, 'registrations' => 18, 'earnings' => 27],
            ['month' => 3, 'registrations' => 24, 'earnings' => 36],
            ['month' => 4, 'registrations' => 31, 'earnings' => 46.50],
            ['month' => 5, 'registrations' => 28, 'earnings' => 42],
            ['month' => 6, 'registrations' => 35, 'earnings' => 52.50],
            ['month' => 7, 'registrations' => 23, 'earnings' => 34.50],
        ];
    }
}
