<?php

return [

    'upi_enabled' => env('PAYMENTS_UPI_ENABLED', true),

    /*
    | Razorpay payment links (optional — set keys to auto-create links instead of UPI URI).
    */
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
    ],

];
