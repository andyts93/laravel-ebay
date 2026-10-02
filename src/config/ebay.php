<?php

return [
    /*
    |--------------------------------------------------------------------------
    | eBay API Credentials
    |--------------------------------------------------------------------------
    |
    | Access credentials
    |
    */
    'client_id' => '',
    'client_secret' => '',
    'ru_name' => '',
    'scopes' => [
        'https://api.ebay.com/oauth/api_scope/sell.inventory',
        'https://api.ebay.com/oauth/api_scope/sell.fulfillment',
        'https://api.ebay.com/oauth/api_scope/sell.account',
        'https://api.ebay.com/oauth/api_scope',
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Settings
    |--------------------------------------------------------------------------
    */

    'sandbox' => true,
    'marketplace_id' => 'EBAY_IT',
    'default_content_language' => 'it-IT',

    /*
    |--------------------------------------------------------------------------
    | PayPal Settings
    |--------------------------------------------------------------------------
    */

    'paypal_email' => '',

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'default_shipping' => [
        'handling_time' => 1,
        'standard_cost' => 5.00,
        'express_cost' => 8.50,
    ],

    'default_return_policy' => [
        'returns_accepted' => true,
        'return_days' => 30,
        'return_shipping_cost_payer' => 'BUYER'
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'token_key' => 'ebay_access_token',
        'policies_ttl' => 60 * 60 * 24 * 30, // 30 giorni
    ]
];
