<?php

return [
    'max_registrations_per_ip' => (int) env('FRAUD_MAX_REGISTRATIONS_PER_IP', 5),
    'ip_window_hours' => (int) env('FRAUD_IP_WINDOW_HOURS', 24),
    'blocked_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FRAUD_BLOCKED_IPS', '')),
    ))),
];
