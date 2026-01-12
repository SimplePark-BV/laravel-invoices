# laravel-invoices

This Laravel package provides an easy to use interface to generate Invoice PDF files with your provided data.

## Installation

You can install the package via Composer:

```bash
composer require simplepark-bv/laravel-invoices
```

The service provider will be automatically registered via Laravel's package auto-discovery.

### Publish Configuration

Publish the configuration file to customize the package settings:

```bash
php artisan vendor:publish --provider="SimpleParkBv\Invoices\InvoiceServiceProvider" --tag="invoices-config"
```

This will create a `config/invoices.php` file where you can customize:

- Currency settings
- Default tax rate
- Payment terms
- PDF generation settings
- Seller information

## Usage

### Basic Example

```php
use SimpleParkBv\Invoices\Invoice;
use SimpleParkBv\Invoices\InvoiceItem;
use SimpleParkBv\Invoices\Buyer;
use Illuminate\Support\Carbon;

// create invoice
$invoice = Invoice::make()
    ->series('2024')
    ->sequence(1)
    ->date(Carbon::now())
    ->language('nl');

// set buyer
$buyer = Buyer::make();
$buyer->name = 'John Doe';
$buyer->email = 'john@example.com';
$buyer->address = '123 Main St';
$buyer->city = 'Amsterdam';
$buyer->postal_code = '1000 AA';
$invoice->buyer($buyer);

// add items using fluent interface
$item = InvoiceItem::make()
    ->title('Product Name')
    ->description('Product description')
    ->quantity(2)
    ->unitPrice(50.00)
    ->taxPercentage(21.0);

$invoice->items([$item]);

// generate and download PDF
return $invoice->download('invoice.pdf');
```

### Fluent Interface

The package supports a fluent interface for building invoices:

```php
$invoice = Invoice::make()
    ->series('2024')
    ->sequence(123)
    ->date('2024-01-15')
    ->language('en');

$item = InvoiceItem::make()
    ->title('Service')
    ->quantity(1)
    ->unitPrice(100.00)
    ->taxPercentage(21.0);

$invoice->items([$item]);
```

### Multiple Items

```php
$items = [
    InvoiceItem::make()
        ->title('Item 1')
        ->quantity(2)
        ->unitPrice(25.00)
        ->taxPercentage(21.0),
    InvoiceItem::make()
        ->title('Item 2')
        ->quantity(1)
        ->unitPrice(50.00)
        ->taxPercentage(9.0),
];

$invoice->items($items);
```

### Forced Total

Sometimes you need to override the calculated total to match an external system:

```php
$invoice->forcedTotal(100.00);

// getTotal() will return 100.00
// getItemsTotal() will still return the calculated sum
```

### Date Handling

The `date()` method (from the `HasInvoiceDates` trait) accepts both Carbon instances and strings. Strings are automatically parsed using `Carbon::parse()`:

```php
// using Carbon
$invoice->date(Carbon::now());

// using string (parsed with Carbon::parse)
$invoice->date('2024-01-15');
```

### Invoice Number

Invoice numbers can be generated from series and/or sequence:

```php
$invoice->series('2024')->sequence(123);
// generates: "2024.00000123"

$invoice->sequence(456);
// generates: "00000456"

$invoice->series('2024');
// generates: "2024"
```

### Validation

The invoice is automatically validated before rendering. You can also validate manually:

```php
try {
    $invoice->validate();
} catch (\SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException $e) {
    // handle validation error
}
```

### Creating from Array

You can create invoices from array data:

```php
$data = [
    'buyer' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
    'date' => '2024-01-15',
    'items' => [
        [
            'title' => 'Product',
            'quantity' => 1,
            'unit_price' => 100.00,
            'tax_percentage' => 21.0,
        ],
    ],
    'series' => '2024',
    'sequence' => 1,
];

$invoice = Invoice::fromArray($data);
```

### Converting to Array

You can also convert invoices to arrays for serialization:

```php
$array = $invoice->toArray();
```

### Available Methods

#### Invoice Methods

- `make()` - Create a new invoice instance
- `fromArray(array $data)` - Create invoice from array
- `toArray()` - Convert invoice to array
- `buyer(Buyer $buyer)` - Set the buyer
- `items(array $items)` - Set all items for the invoice (replaces existing items)
- `series(?string $series)` - Set invoice series
- `sequence(?int $sequence)` - Set invoice sequence number
- `date(Carbon|string $date)` - Set invoice date (accepts Carbon instance or string, strings are parsed with Carbon::parse)
- `language(string $language)` - Set invoice language
- `logo(?string $logoPath)` - Set custom logo path
- `template(string $template)` - Set custom template
- `forcedTotal(float $amount)` - Override calculated total
- `validate()` - Validate invoice data
- `render()` - Generate PDF instance
- `download(?string $filename)` - Download PDF
- `stream(?string $filename)` - Stream PDF in browser
- `isRendered()` - Check if PDF has been rendered
- `clearPdf()` - Clear PDF instance to free memory
- `getItemsTotal()` - Get sum of all items
- `getTaxAmount()` - Calculate total tax amount
- `getSubTotal()` - Calculate subtotal (excluding tax)
- `getTotal()` - Get grand total
- `getFormattedTotal()` - Get formatted total with currency
- `getTaxGroups()` - Get unique tax percentages
- `getTaxAmountForTaxGroup(float $taxPercentage)` - Get tax amount for specific tax group
- `getSubTotalForTaxGroup(float $taxPercentage)` - Get subtotal for specific tax group
- `getFormattedDate()` - Get formatted invoice date
- `getFormattedDueDate()` - Get formatted due date
- `getNumber()` - Get invoice number

#### InvoiceItem Methods

- `make()` - Create a new item instance
- `title(string $title)` - Set item title
- `description(?string $description)` - Set item description
- `quantity(float|int $quantity)` - Set quantity
- `unitPrice(float|int $unitPrice)` - Set unit price
- `taxPercentage(?float $taxPercentage)` - Set tax percentage
- `getTotal()` - Calculate item total (quantity * unit_price)
- `getFormattedTaxPercentage()` - Get formatted tax percentage

## Validation Requirements

Before rendering, the invoice must have:

- A buyer with at least a name
- At least one item
- Each item must have:
  - A non-empty title
  - A quantity greater than 0
  - A unit_price greater than or equal to 0
  - A tax_percentage between 0 and 100, or null

## Development

This package uses Laravel Sail for local development and Orchestra Testbench for testing.

### Initial Setup

1. Install dependencies:
```bash
composer install
```

2. Start Sail:
```bash
./vendor/bin/sail up -d
```

### Running Tests

```bash
./vendor/bin/sail phpunit
```

### Code Quality

Run PHPStan for static analysis:
```bash
./vendor/bin/phpstan analyse
```

Run Pint for code formatting:
```bash
./vendor/bin/pint
```

## Troubleshooting

### Invoice validation fails

Make sure:
- Buyer is set with at least a name
- At least one item is added
- All items have valid data (title, quantity > 0, unit_price >= 0)

### PDF generation fails

Check that:
- All required translations are available for the selected language
- Logo path is valid (if using custom logo)
- Template exists and is accessible

### Language not supported

The package validates language codes against available translation files. Supported languages are automatically detected from the `resources/lang` directory.
