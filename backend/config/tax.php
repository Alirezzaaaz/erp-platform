<?php

return [
    'mode' => env('TAX_MODE', 'mock'),
    'api_url' => env('TAX_API_URL', 'https://sandboxrc.tax.gov.ir/req/api/self-tsp'),
    'taxpayer_id' => env('TAX_TAXPAYER_ID'),
    'economic_code' => env('TAX_ECONOMIC_CODE'),
    'private_key_path' => env('TAX_PRIVATE_KEY_PATH', storage_path('app/tax/private.pem')),
    'certificate_path' => env('TAX_CERT_PATH', storage_path('app/tax/certificate.pem')),
    'seller_economic_code' => env('TAX_SELLER_ECONOMIC_CODE'),
    'timeout' => env('TAX_TIMEOUT', 30),
    'retry_max' => env('TAX_RETRY_MAX', 3),
    'retry_delay_seconds' => env('TAX_RETRY_DELAY', 60),
    'vat_rate' => env('TAX_VAT_RATE', 9),
];
