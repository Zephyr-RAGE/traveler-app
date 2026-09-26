<?php

return [
    'app' => [
        'token_secret' => env('APP_TOKEN_SECRET'),
        'debug' => env('APP_DEBUG', false),
    ],

    'apis' => [
        'weather_key' => env('WEATHER_API_KEY'),
        'exchange_key' => env('EXCHANGE_API_KEY'),
    ],
];