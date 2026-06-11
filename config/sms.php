<?php

return [
    'driver' => env('SMS_DRIVER', 'log'),

    'debug_code' => (bool) env('SMS_DEBUG_CODE', false),

    'smsru' => [
        'api_id' => env('SMSRU_API_ID'),
        'from' => env('SMSRU_FROM'),
        'test' => (bool) env('SMSRU_TEST', false),
        'timeout' => (int) env('SMSRU_TIMEOUT', 12),
    ],
];
