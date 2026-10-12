<?php

return [
    'api_base_url' => env('SHOP_API_BASE_URL', 'http://api.thegift.lk'),
    'merchant_id' => env('SHOP_MERCHANT_ID'),
    'merchant_app_token' => env('SHOP_MERCHANT_APP_TOKEN'),
    'customer_phone' => env('SHOP_CUSTOMER_PHONE', '0771234567'),
    'customer_email' => env('SHOP_CUSTOMER_EMAIL', 'customer@example.com'),
    'custom_param_1' => env('SHOP_CUSTOM_PARAM_1', 'shop-test'),
    'custom_param_2' => env('SHOP_CUSTOM_PARAM_2', 'v1'),
];
