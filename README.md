<div align="left">
  <a href="https://simplepark.nl" target="_blank">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://www.simplepark.nl/images/github/laravel-invoices-banner-dark.png">
      <img alt="Laravel Invoices Banner" src="https://www.simplepark.nl/images/github/laravel-invoices-banner-light.png">
    </picture>
  </a>

  <h1>Laravel Invoices</h1>
</div>

A Laravel package for generating Invoice and Usage Receipt PDFs with customizable templates and multilingual support.

## Installation

```bash
composer require simplepark-bv/laravel-invoices
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="SimpleParkBv\Invoices\InvoiceServiceProvider" --tag="invoices-config"
```

Customize currency, tax rates, payment terms, PDF settings, and seller information in `config/invoices.php`.

### Publish Translations

```bash
php artisan vendor:publish --provider="SimpleParkBv\Invoices\InvoiceServiceProvider" --tag="invoices-lang"
```

Modify labels, table headers, footer text, and filename prefixes in `lang/vendor/invoices/`. Available languages: `en`, `nl`.

## Usage

### Invoices

```php
use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\InvoiceItem;
use SimpleParkBv\Invoices\Models\Buyer;

$invoice = Invoice::make()
    ->series('2024')
    ->sequence(1)
    ->date('2024-01-15')
    ->language('nl')
    ->buyer([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'address' => '123 Main St',
        'city' => 'Amsterdam',
        'postal_code' => '1000 AA',
    ])
    ->items([
        InvoiceItem::make([
            'title' => 'Product Name',
            'description' => 'Product description',
            'quantity' => 2,
            'unit_price' => 50.00,
            'tax_percentage' => 21.0,
        ]),
    ]);

return $invoice->download(); // or ->stream() for browser preview
```

**Invoice Numbers**: Combine `series()` and `sequence()` to generate formatted invoice numbers (e.g., "2024.00000123").

**Discounts**: Use negative `unit_price` values for discount items (quantities must be positive).

**Expected Totals**: Set an expected total with `expectedTotal(100.00)` for validation. When the invoice is rendered, if the expected total differs from the calculated total, an error will be logged.

### Usage Receipts

```php
use SimpleParkBv\Invoices\Models\UsageReceipt;
use SimpleParkBv\Invoices\Models\UsageReceiptItem;
use SimpleParkBv\Invoices\Models\Buyer;

$receipt = UsageReceipt::make()
    ->date('2024-01-15')
    ->language('nl')
    ->title('Custom Title') // optional, falls back to translation
    ->documentId('DOC-12345')
    ->userId('USER-001')
    ->buyer([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ])
    ->items([
        UsageReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Premium',
            'price' => 12.50,
        ]),
    ])
    ->note('Optional note for the receipt');

return $receipt->download(); // filename is locale-aware
```

**Locale-Aware Filenames**: The package generates filenames based on the selected language (e.g., `gebruiksbevestiging-2026-01-19-14-30-00.pdf` for Dutch).

### Common Features

Both `Invoice` and `UsageReceipt` support:

- **Fluent interface** and **array-based** creation
- **Data arrays**: `Invoice::make($data)` or `UsageReceipt::make($data)`
- **Serialization**: `->toArray()` for JSON/API responses
- **Validation**: Automatic validation before rendering, or manual with `->validate()`
- **Output**: `->download(?string $filename)` or `->stream(?string $filename)`
- **Custom templates**: `->template('custom.template')`
- **Custom logos**: `->logo('/path/to/logo.png')`
- **Date parsing**: Accepts Carbon instances or strings (parsed with `Carbon::parse()`)

### Creating from Arrays

```php
$invoice = Invoice::make([
    'buyer' => ['name' => 'John Doe', 'email' => 'john@example.com'],
    'date' => '2024-01-15',
    'series' => '2024',
    'sequence' => 1,
    'items' => [
        ['title' => 'Product', 'quantity' => 1, 'unit_price' => 100.00, 'tax_percentage' => 21.0],
    ],
]);

$receipt = UsageReceipt::make([
    'buyer' => ['name' => 'Jane Doe'],
    'date' => '2024-01-15',
    'document_id' => 'DOC-001',
    'user_id' => 'USER-001',
    'items' => [
        ['user' => 'Jane Doe', 'identifier' => 'XYZ', 'start_date' => '2024-01-15 10:00', 'end_date' => '2024-01-15 12:00', 'category' => 'Basic', 'price' => 10.00],
    ],
]);
```

## Validation Requirements

**Invoices** must have:
- A buyer with at least a name
- At least one item with: non-empty title, quantity > 0, unit price, tax percentage (0-100 or null)

**Usage Receipts** must have:
- A buyer with at least a name
- At least one item with: user, identifier, start date, end date, category, and price

## Development

```bash
composer install
./vendor/bin/sail up -d
./vendor/bin/sail phpunit
./vendor/bin/phpstan analyse
./vendor/bin/pint
```

## Troubleshooting

**Validation fails**: Ensure buyer and items meet requirements above.

**PDF generation fails**: Verify translations exist for the selected language and logo path is valid.

**Language not supported**: Supported languages are detected from `resources/lang/`. Add custom translations by publishing the language files.

## License

MIT
