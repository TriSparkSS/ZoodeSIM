<?php

return [
    'enabled' => env('API_LOGGING_ENABLED', true),

    'max_body_bytes' => (int) env('API_LOGGING_MAX_BODY_BYTES', 65536),

    'slow_ms' => (int) env('API_LOGGING_SLOW_MS', 1000),

    'mask' => '********',
];
