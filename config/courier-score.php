<?php

return [
    'url' => env('COURIER_SCORE_URL', 'https://bdcourier.com/api/pro/courier-check'),

    'api_key' => env('COURIER_SCORE_API_KEY', 'bdc_zaODR2qe5F4YXS2iOqEjeOIW6yJ4iZGX4laku0eNxlx4uhfAhz2alzC2xIkE'),

    'cache_days' => (int) env('COURIER_SCORE_CACHE_DAYS', 7),
];
