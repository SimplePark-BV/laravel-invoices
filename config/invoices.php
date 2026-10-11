<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Currency Symbol
    |--------------------------------------------------------------------------
    |
    | The symbol shown before amounts. The decimal and thousands separators
    | follow the language of the document, see the invoices::format
    | translations.
    |
    */
    'currency_symbol' => env('INVOICES_CURRENCY_SYMBOL', '€'),

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | The language of new documents: 'nl' (Dutch) or 'en' (English), or any
    | language you added by publishing the translations. Override it per
    | document with language().
    |
    */
    'default_language' => env('INVOICES_DEFAULT_LANGUAGE', 'nl'),

    /*
    |--------------------------------------------------------------------------
    | Payment Terms
    |--------------------------------------------------------------------------
    |
    | The number of days after the invoice date an invoice is due. Override
    | it per invoice with paymentTermsDays(), or set the due date itself
    | with dueDate().
    |
    */
    'default_payment_terms_days' => (int) env('INVOICES_PAYMENT_TERMS_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Dates
    |--------------------------------------------------------------------------
    |
    | The timezone dates are shown in (null uses the app timezone), and the
    | PHP date formats of dates and of the sessions on usage receipts.
    | Month and day names follow the language of the document.
    |
    */
    'timezone' => env('INVOICES_TIMEZONE'),

    'date_format' => env('INVOICES_DATE_FORMAT', 'd-m-Y'),

    'datetime_format' => env('INVOICES_DATETIME_FORMAT', 'd-m-Y H:i'),

    /*
    |--------------------------------------------------------------------------
    | PDF Settings
    |--------------------------------------------------------------------------
    |
    | The paper and the font of the PDFs. By default the bundled Poppins font
    | is used. Set a font file (a .ttf, relative to the base path of the
    | app) to use your own font for all text, or only a font name that
    | dompdf knows, such as 'DejaVu Sans'. Other dompdf options, such as
    | the font cache directory, come from the config of barryvdh/laravel-dompdf.
    |
    */
    'pdf' => [
        'paper_size' => env('INVOICES_PDF_PAPER_SIZE', 'a4'),
        'orientation' => env('INVOICES_PDF_ORIENTATION', 'portrait'),
        'font' => env('INVOICES_PDF_FONT', 'Poppins'),
        'font_file' => env('INVOICES_PDF_FONT_FILE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Seller
    |--------------------------------------------------------------------------
    |
    | The company that issues the documents. The name is required; documents
    | without a seller name fail validation. Override the seller per
    | document with seller().
    |
    */
    'seller' => [
        'name' => env('INVOICES_SELLER_NAME'),
        'address' => env('INVOICES_SELLER_ADDRESS'),
        'postal_code' => env('INVOICES_SELLER_POSTAL_CODE'),
        'city' => env('INVOICES_SELLER_CITY'),
        'country' => env('INVOICES_SELLER_COUNTRY'),
        'email' => env('INVOICES_SELLER_EMAIL'),
        'phone' => env('INVOICES_SELLER_PHONE'),
        'website' => env('INVOICES_SELLER_WEBSITE'),
        'registration_number' => env('INVOICES_SELLER_REGISTRATION_NUMBER'),
        'tax_id' => env('INVOICES_SELLER_TAX_ID'),
        'bank_account' => env('INVOICES_SELLER_BANK_ACCOUNT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    |
    | The logo of the documents: an absolute path, a path relative to the
    | base path of the app, or a data URI, of a PNG, JPG, GIF or SVG image.
    | Null shows no logo. A logo that cannot be read is left out and
    | logged as a warning. Keep the image small (about 600 pixels wide):
    | large images make rendering slow and the PDFs big.
    |
    */
    'logo' => env('INVOICES_LOGO'),
];
