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
    | Decimal Separator
    |--------------------------------------------------------------------------
    |
    | The character used to separate the decimal part of currency amounts.
    | Common values: ',' (European) or '.' (US/UK).
    |
    */
    'decimal_separator' => env('INVOICES_DECIMAL_SEPARATOR', ','),

    /*
    |--------------------------------------------------------------------------
    | Thousands Separator
    |--------------------------------------------------------------------------
    |
    | The character used to separate thousands in currency amounts.
    | Common values: '.' (European) or ',' (US/UK).
    |
    */
    'thousands_separator' => env('INVOICES_THOUSANDS_SEPARATOR', '.'),

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
    | Calculation Precision
    |--------------------------------------------------------------------------
    |
    | Precision settings for tax calculations to prevent rounding drift.
    | These values control how many decimal places are used when rounding
    | monetary values and tax percentages.
    |
    */
    'precision' => [
        'monetary' => env('INVOICES_PRECISION_MONETARY', 2),
        'tax_percentage' => env('INVOICES_PRECISION_TAX_PERCENTAGE', 2),
        'tax_percentage_epsilon' => env('INVOICES_PRECISION_TAX_PERCENTAGE_EPSILON', 0.005),
    ],

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
    | Default Language
    |--------------------------------------------------------------------------
    |
    | Default language for invoices. Supported: 'nl' (Dutch), 'en' (English).
    | Can be overridden per invoice using language() method.
    |
    */
    'default_language' => env('INVOICES_DEFAULT_LANGUAGE', 'nl'),

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
        'font' => env('INVOICES_PDF_FONT', 'Montserrat'),
        'font_file' => env('INVOICES_PDF_FONT_FILE', null),
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
        'name' => env('INVOICES_SELLER_NAME', 'Your Company Name'),
        'address' => env('INVOICES_SELLER_ADDRESS', 'Street Address'),
        'postal_code' => env('INVOICES_SELLER_POSTAL_CODE', '1234 AB'),
        'city' => env('INVOICES_SELLER_CITY', 'City'),
        'country' => env('INVOICES_SELLER_COUNTRY', 'Nederland'),
        'email' => env('INVOICES_SELLER_EMAIL', 'info@example.com'),
        'kvk' => env('INVOICES_SELLER_KVK', '12345678'),
        'btw' => env('INVOICES_SELLER_BTW', 'NL000000000B00'),
        'iban' => env('INVOICES_SELLER_IBAN', 'NL00 BANK 0000 0000 00'),
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
    'logo' => env('INVOICES_LOGO', null),
];
