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
];

