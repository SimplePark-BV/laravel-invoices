<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency code for invoices (ISO 4217 format).
    |
    */
    'currency' => env('INVOICES_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency Symbol
    |--------------------------------------------------------------------------
    |
    | The default currency symbol to display.
    |
    */
    'currency_symbol' => env('INVOICES_CURRENCY_SYMBOL', '€'),

    /*
    |--------------------------------------------------------------------------
    | Default Tax Rate
    |--------------------------------------------------------------------------
    |
    | The default tax rate percentage (e.g., 21 for 21%).
    |
    */
    'default_tax_rate' => env('INVOICES_DEFAULT_TAX_RATE', 21),

    /*
    |--------------------------------------------------------------------------
    | Default Payment Terms
    |--------------------------------------------------------------------------
    |
    | Default number of days until payment is due.
    |
    */
    'default_payment_terms_days' => env('INVOICES_PAYMENT_TERMS_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | PDF Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for PDF generation.
    |
    */
    'pdf' => [
        'paper_size' => env('INVOICES_PDF_PAPER_SIZE', 'a4'),
        'orientation' => env('INVOICES_PDF_ORIENTATION', 'portrait'),
        'font' => env('INVOICES_PDF_FONT', 'dejavu-sans'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Seller
    |--------------------------------------------------------------------------
    |
    | Default seller information used when creating invoices.
    |
    */
    'seller' => [
        'name' => env('INVOICES_SELLER_NAME', 'SimplePark B.V.'),
        'address' => env('INVOICES_SELLER_ADDRESS', 'Valstraat 3'),
        'postal_code' => env('INVOICES_SELLER_POSTAL_CODE', '5491BH'),
        'city' => env('INVOICES_SELLER_CITY', 'Sint-Oedenrode'),
        'country' => env('INVOICES_SELLER_COUNTRY', 'Nederland'),
        'email' => env('INVOICES_SELLER_EMAIL', 'info@simplepark.nl'),
        'kvk' => env('INVOICES_SELLER_KVK', '96305827'),
        'btw' => env('INVOICES_SELLER_BTW', 'NL867555257B01'),
        'iban' => env('INVOICES_SELLER_IBAN', 'NL59 RABO 0107 4988 55'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    |
    | Default logo path for invoices. Can be absolute path or relative to
    | the package resources directory. Set to null to disable logo.
    |
    | Supported formats: PNG (recommended), JPG, JPEG, GIF, SVG
    | Note: SVG has limited support in PDFs and may render incorrectly.
    | PNG is recommended for best compatibility and transparency support.
    |
    */
    'logo' => env('INVOICES_LOGO', __DIR__.'/../resources/images/logo.png'),
];
