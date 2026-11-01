<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    */
    'merchant_id' => env('MIDTRANS_MERCHANT_ID', 'M891031882'),
    'client_key' => env('MIDTRANS_CLIENT_KEY', 'Mid-client-5jMyzfCsWlculDpZ'),
    'server_key' => env('MIDTRANS_SERVER_KEY', 'Mid-server-fvhZs-kPO8sHaAc1ZOK86gBb'),
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Midtrans API Endpoints
    |--------------------------------------------------------------------------
    */
    'api_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://api.midtrans.com/v2'
        : 'https://api.sandbox.midtrans.com/v2',

    'snap_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://app.midtrans.com/snap/v1'
        : 'https://app.sandbox.midtrans.com/snap/v1',
];
