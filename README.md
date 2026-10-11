<div>
  <a href="https://simplepark.nl" target="_blank">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/SimplePark-BV/.github/refs/heads/main/assets/repository_banners/simplepark_laravel_invoices/dark.svg">
      <img alt="SimplePark Laravel Invoices" src="https://raw.githubusercontent.com/SimplePark-BV/.github/refs/heads/main/assets/repository_banners/simplepark_laravel_invoices/light.svg">
    </picture>
  </a>
</div>

A Laravel package that generates invoice and usage receipt PDFs, in Dutch and English, with dompdf.

Upgrading from v3? Read the [upgrade guide](UPGRADE.md).

## Requirements

- PHP 8.3 or higher
- Laravel 12 or 13
- The `gd` extension, to show PNG, JPG and GIF logos

## Installation

```bash
composer require simplepark-bv/laravel-invoices
```

Publish the config and set at least the name of the seller:

```bash
php artisan vendor:publish --tag="invoices-config"
```

```env
INVOICES_SELLER_NAME="SimplePark B.V."
INVOICES_LOGO=resources/images/logo.png
```

The config holds the seller details, the logo, the default language, the payment terms, the date formats and timezone, the currency symbol, and the paper and font of the PDFs. Settings that are not in your published config fall back to the package defaults, so new settings work without publishing again.

The PDFs are rendered with [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf), and its config is kept. Make sure its font cache directory (`storage/fonts` by default) is writable; the package creates it when it is missing.

## Invoices

```php
use SimpleParkBv\Invoices\Models\Invoice;

$invoice = Invoice::make()
    ->language('nl')
    ->serial('2026.0001')
    ->date('2026-10-11')
    ->buyer([
        'name' => 'Jan de Vries',
        'address' => 'Keizersgracht 123',
        'postal_code' => '1015 CJ',
        'city' => 'Amsterdam',
        'email' => 'jan@example.com',
    ])
    ->items([
        ['title' => 'Parking', 'description' => 'Zone 1001', 'quantity' => 2, 'unit_price' => 12.10, 'tax_percentage' => 21],
        ['title' => 'Discount', 'unit_price' => -2.00, 'tax_percentage' => 21],
    ]);

return $invoice->download(); // or ->stream() to show it in the browser
```

**Amounts include tax.** The unit price is the price of one unit including tax, with at most 2 decimals; a negative unit price is a discount. Amounts may be floats, integers or numeric strings such as `'12.50'`. Quantities may be fractional, such as `1.5`. A tax percentage of `null` means no tax applies; `0` is a 0% rate.

**Totals add up.** All amounts are calculated in whole cents. The line totals add up to the total, and the tax is calculated once per rate over the amounts with that rate. The invoice shows the subtotal excluding tax, and per rate the tax and the amount it is calculated over.

**Numbers.** Set the complete invoice number with `serial('2026.0001')`, or combine `series('2026')` and `sequence(1)` into `2026.00000001`.

**Concepts.** An invoice without a date is a concept: it is titled "Factuur CONCEPT" when it has no number, shows no dates, and says it is not official yet.

**Payment.** The footer of an issued invoice depends on its state:

| State | Footer |
|---|---|
| Total above zero | Asks to pay the amount before the due date by bank transfer |
| `->paymentMethod(PaymentMethod::DirectDebit)` | Says the amount is collected by direct debit around the due date |
| `->paid()` | Says the amount has already been paid; the due date is not shown |
| Total of zero | Says nothing has to be paid |
| Total below zero | A credit invoice ("Creditfactuur"): says the amount is transferred to the buyer |

Replace the footer with `->footerMessage('...')`, shown as plain text with `:amount`, `:date` (the due date) and `:number` replaced, and the footer of a concept with `->conceptFooterMessage('...')`. The footer sits in the bottom margin of every page, which fits about five lines; a longer footer runs into the content.

**Due date.** The due date is the invoice date plus the payment terms (`invoices.default_payment_terms_days`, 30 days by default). Change the terms with `->paymentTermsDays(14)`, or set the date itself with `->dueDate('2026-11-01')`.

**Expected total.** Pass the total you charged with `->expectedTotal(22.20)`. Validating or rendering the invoice throws an `ExpectedTotalMismatchException` when its total differs by even one cent, so a PDF never shows another amount than the one charged.

## Usage receipts

A usage receipt confirms the sessions a buyer used, such as parking sessions. It is not a payment request.

```php
use SimpleParkBv\Invoices\Models\UsageReceipt;

$receipt = UsageReceipt::make()
    ->language('nl')
    ->date('2026-10-11 14:30')
    ->documentId('PS-2026-000123')
    ->userId('USR-000042')
    ->buyer(['name' => 'Jan de Vries', 'email' => 'jan@example.com'])
    ->items([
        [
            'user' => 'Jan de Vries',
            'identifier' => 'AB-123-C',
            'start_date' => '2026-10-02 10:00',
            'end_date' => '2026-10-02 12:00',
            'category' => 'Zone 1001',
            'price' => 12.50,
        ],
    ])
    ->note('Thank you for parking.');

return $receipt->download(); // gebruiksbevestiging-PS-2026-000123.pdf
```

Without lines, the total is the sum of the session prices. Add a cost breakdown with `lines()`, such as fees and discounts including tax; the total is then the sum of the lines, and the receipt shows the tax they include per rate below the total:

```php
$receipt->lines([
    ['label' => 'Parking costs', 'amount' => 12.50],
    ['label' => 'Service fee', 'amount' => 0.35, 'tax_percentage' => 21],
    ['label' => 'Discount', 'amount' => -1.00, 'tax_percentage' => 21],
]);
```

The buyer of a receipt needs no name. Sessions may end at the moment they start, but not before.

## Building documents

Every document, item, line and party can be built with fluent setters or from an array: `Invoice::make([...])`, `InvoiceItem::make([...])`, `Buyer::make([...])`. Array keys are the snake_case names of the setters (camelCase works too). Unknown keys throw, so a typo never silently leaves a field empty. `toArray()` returns the same keys, so `Invoice::make($invoice->toArray())` gives an identical invoice.

```php
$invoice = Invoice::make([
    'buyer' => ['name' => 'Jan de Vries'],
    'serial' => '2026.0001',
    'date' => '2026-10-11',
    'items' => [
        ['title' => 'Parking', 'unit_price' => 12.10, 'tax_percentage' => 21],
    ],
]);
```

Dates accept any `DateTimeInterface` (Carbon included) or a string, and are stored as `CarbonImmutable`. They are shown in the `invoices.timezone` (the app timezone by default), with month names in the document language; strings without a timezone are read in that timezone, so `'2026-10-11'` is always shown as 11-10-2026.

Array values must have the type of their setter: strings for texts and ids, numbers or numeric strings for amounts, booleans for `paid`. `null` is accepted wherever `toArray()` can return it.

The seller comes from the config. Set another one per document with `->seller([...])`.

## Output

| Method | Returns |
|---|---|
| `toPdf()` | The contents of the PDF, e.g. to store or attach it |
| `download(?string $filename = null)` | A response that downloads the PDF |
| `stream(?string $filename = null)` | A response that shows the PDF in the browser |
| `toHtml()` | The HTML the PDF is rendered from |
| `getFilename()` | The default file name, e.g. `factuur-2026.0001.pdf` |
| `getTotals()` | The totals, in cents and euros, with the tax per rate |

Every call renders the current state of the document, so changes after an earlier download are never lost. To attach a document to a mail:

```php
use Illuminate\Mail\Attachment;

Attachment::fromData(fn () => $invoice->toPdf(), $invoice->getFilename())->withMime('application/pdf');
```

## Validation and exceptions

Rendering validates the document first. Call `validate()` to do so yourself.

- An invoice needs a buyer with a name, and at least one item with a title, a quantity above zero and a unit price.
- A usage receipt needs a buyer, and at least one session with a user, an identifier, a category, a start, an end and a price of zero or more.
- Both need a seller with a name.

Every exception implements `SimpleParkBv\Invoices\Exceptions\InvoicesException`:

| Exception | When |
|---|---|
| `InvalidInvoiceException`, `InvalidUsageReceiptException` | Invalid or incomplete data. Both extend `InvalidDocumentException`, an `InvalidArgumentException`. |
| `ExpectedTotalMismatchException` | The total differs from the expected total. |
| `UnsupportedLanguageException` | The language has no translations. |
| `RenderException` | The HTML or PDF could not be rendered, e.g. a missing template. |

A logo that cannot be read is left out and logged as a warning, so it never stops a PDF.

## Customizing

**Languages.** Dutch (`nl`) and English (`en`) are included. Publish the translations to change texts; overriding only the keys you change is enough, the other keys fall back to the package:

```bash
php artisan vendor:publish --tag="invoices-lang"
```

Add a language by adding a directory to `lang/vendor/invoices`, e.g. `de` with `invoice.php`, `usage-receipt.php` and `format.php` (the decimal and thousands separators). A regional language such as `nl_BE` falls back to `nl`.

**Templates.** Publish the views with `--tag="invoices-views"` to change them, or render a view of your app with `->template('pdf.invoice')`. Views receive the document (as `$invoice` or `$usageReceipt`, and as `$document`), its `$totals`, a `$format` formatter for amounts, percentages and dates, and the `$logo` data URI.

**Fonts.** The PDFs use the bundled Poppins font. Use your own TrueType font for all text with `INVOICES_PDF_FONT="My Font"` and `INVOICES_PDF_FONT_FILE=resources/fonts/MyFont.ttf` (relative to the base path of the app), or only a font name dompdf knows, such as `INVOICES_PDF_FONT="DejaVu Sans"`. A font file that cannot be read falls back to Poppins, with a warning.

**Logo.** Keep the logo small, about 600 pixels wide: large images make rendering slow and the PDFs big.

## Development

```bash
composer install
composer check   # validate, format check, static analysis and tests
composer format  # fix the formatting
```

Without PHP installed locally, run the same in Docker:

```bash
docker run --rm -v "$PWD":/app -w /app laravelsail/php84-composer:latest composer check
```

## License

MIT
