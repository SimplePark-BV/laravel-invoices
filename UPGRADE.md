# Upgrade guide

## From v3 to v4

v4 is a rewrite of the internals: money is calculated in whole cents, documents render a fresh PDF on every call, array input is strict, and there is one exception hierarchy. Most fluent setters kept their names, so most code keeps working; the changes below are the ones that can break it.

### Requirements

- PHP 8.3 or higher (was 8.2).
- Laravel 12 or 13 (was 11 or 12).
- `dompdf/dompdf` 3.1.6 or higher, the first version without the known security advisories.

### High impact

**Expected totals always throw.** `expectedTotal()` used to log a mismatch and only throw with `expectedTotal($amount, true)`, and it accepted a difference of up to one cent, depending on float noise. Now a mismatch of even one cent throws an `ExpectedTotalMismatchException` from `validate()`, `toPdf()`, `download()` and `stream()`, and nothing is logged. The second parameter is removed, and `expectedTotal(null)` removes the check. To keep logging instead, catch the exception.

**Amounts have at most 2 decimals.** Unit prices, prices, line amounts and expected totals with more decimals throw. Strings must use a dot as decimal separator (`'12.50'`); `'12,50'` throws.

**Rendering is stateless.** `render()`, `isRendered()`, `clearPdf()` and the public `$pdf`, `$output`, `$options` and `$paperOptions` properties are removed. Every `download()`, `stream()` and the new `toPdf()` call renders the current document. Replace `$document->stream()->getContent()` and `$document->render()->pdf->output()` with `$document->toPdf()`. `stream()` no longer adds no-cache headers.

**Exceptions.** Every exception implements `InvoicesException`.

| v3 | v4 |
|---|---|
| `InvalidInvoiceException`, `InvalidUsageReceiptException` (`RuntimeException`) | Same names, now extending `InvalidDocumentException`, an `InvalidArgumentException` (so a `LogicException`) |
| `InvalidInvoiceItemException`, `InvalidUsageReceiptItemException` | Removed: items and lines throw the exception of their document |
| `RuntimeException` for an unsupported language | `UnsupportedLanguageException` |
| `InvalidInvoiceException` for any render failure, also of receipts | `RenderException` (a `RuntimeException`), also for dompdf errors |
| Mismatching expected total: logged, or `InvalidInvoiceException` | `ExpectedTotalMismatchException`, with `expectedCents` and `actualCents` |

**Unknown array keys throw.** `make()` and `fill()` used to call any public method with the name of a key and silently skip unknown keys. Now only the documented keys are accepted (snake_case or camelCase), unknown keys throw, and values must have the type of their setter (an integer serial or postal code throws; pass a string). `null` is accepted wherever `toArray()` can return it, so `make($document->toArray())` gives an identical document. An invalid buyer or seller array throws the exception of its document.

**The seller has no default name.** The seller config defaulted to placeholders such as "Your Company Name". Now its fields default to `null`, and a document without a seller name fails validation. Set `INVOICES_SELLER_NAME` or `invoices.seller.name`.

### Medium impact

**Interfaces.** `InvoiceInterface`, `InvoiceItemInterface`, `UsageReceiptInterface`, `UsageReceiptItemInterface` and `PartyInterface` are removed; type against the classes instead. `TaxableInterface::getTotal(): float` is now `getTotalCents(): int`.

**Totals.** One `getTotals()` method replaces the separate total getters, and its numbers are the ones on the PDF.

| v3 | v4 |
|---|---|
| `getItemsTotal()`, `getTotal()`, `getFormattedTotal()` | `getTotal()`, or `getTotals()->getTotalCents()`; format with `getFormatter()->money()` |
| `getSubTotal()`, `getFormattedSubTotal()` (a float) | `getSubtotal()`, or `getTotals()->getSubtotalCents()` |
| `getTaxAmount()` (rounded differently than the PDF) | `getTaxAmount()`, the sum of the tax per rate as on the PDF |
| `getTaxGroups()`, `getTaxAmountForTaxGroup()`, `getSubTotalForTaxGroup()` | `getTotals()->getTaxGroups()`, a list of `TaxGroup` with the percentage, gross, tax and net amounts |
| `getLinesTotal()`, `getLinesTaxGroups()`, `getLinesTaxAmountForTaxGroup()`, `getLinesTaxAmount()` | `getTotals()` of the receipt |

**Dates.** Date setters accept any `DateTimeInterface` or a string, store a `CarbonImmutable` copy, and the getters return `CarbonImmutable`. Before, other Carbon classes were turned into strings (losing their timezone), and the getters returned the mutable instance itself. An empty string throws instead of meaning "now", and dates that do not exist (`2026-02-30`) throw. Dates are shown in `invoices.timezone`, or the app timezone, and strings without a timezone are read in that timezone.

**Invoices.**
- `payUntilDays()` and `getPayUntilDays()` are renamed to `paymentTermsDays()` and `getPaymentTermsDays()`; the config key `invoices.default_payment_terms_days` is unchanged. New: `dueDate()` and `getDueDate()`.
- `getCustomFooterMessage()` and `getCustomConceptFooterMessage()` return the text as it was set, or `null`, instead of escaped HTML or `''`.
- `getFooterMessage()` uses the language of the invoice, also outside rendering. A total of zero now shows a "nothing to pay" text instead of asking to pay € 0,00. New: `paymentMethod(PaymentMethod::DirectDebit)` and `paid()`, each with their own text.
- `getFilename()` uses `getNumber()`, so invoices numbered with `series()` and `sequence()` no longer download as `factuur-CONCEPT.pdf`. Characters such as `/` are replaced.
- A negative total is titled "Creditfactuur" / "Credit invoice". The due date is only shown while payment is due.

**Items.**
- `InvoiceItem::taxRate()` is removed; use `taxPercentage(21)`.
- The quantity of an `InvoiceItem` defaults to 1 (was 0), and a unit price is required.
- `getTotal()` is rounded to whole cents.
- `UsageReceiptItem::dateFormat()`, `getFormattedStartDate()`, `getFormattedEndDate()` and `getFormattedPrice()` are removed; set the format of the sessions with `UsageReceipt::dateTimeFormat()` or `invoices.datetime_format`.
- `UsageReceiptLine::getFormattedAmount()` and `getFormattedTaxPercentage()` are removed.
- A session may now end at the moment it starts.

**Usage receipts.**
- The payment terms, due date and `isIssued()` methods are removed: they meant nothing on a receipt.
- The buyer no longer needs a name.
- `getFilename()` uses the document id: `gebruiksbevestiging-{documentId}.pdf`, or the date without one. The English filename is `usage-confirmation-…` (was `usage-receipt-…`).
- The tax of the lines is shown below the total as "Waarvan 21% btw" / "Of which 21% VAT", because the line amounts already include it.

**Parties.** `Seller::make()` takes an array like `Buyer::make()`; the seller from the config is `Seller::fromConfig()`. The public seller properties are replaced by setters, and documents have a `seller()` setter. `Buyer::fromParty()` is removed. Names are optional (`getName(): ?string`). Texts are trimmed, and empty texts become `null`.

**Languages.** Any language with translations is accepted, including one you add to `lang/vendor/invoices`. Codes are normalised (`NL` becomes `nl`, and `nl_BE` falls back to `nl`). `getAvailableLanguages()` and `clearLanguageCache()` are removed. Rendering only switches the translator to the document language, and no longer changes the app locale.

**Templates.** `template()` takes a full view name: `template('invoice.index')` becomes `template('invoices::invoice.index')`, and a view of your app works as well, e.g. `template('pdf.invoice')`.

### Low impact

**Config.**
- Removed: `currency` (unused), `decimal_separator` and `thousands_separator` (the separators now follow the document language, from the new `invoices::format` translations), and `precision` (amounts are always whole cents).
- New: `timezone`, `date_format`, `datetime_format` and `seller.phone` (shown on both documents).
- The config is merged recursively, so nested keys added in later versions reach a published config.
- Relative logo and font paths are read from the base path of the app (logos were read from the working directory).
- A logo that cannot be read is logged as a warning and left out, instead of drawing a grey box. Without a logo nothing is drawn either.

**dompdf.** The package used to replace all dompdf options. That ignored your `config/dompdf.php`, wrote the font cache into `vendor/`, and could corrupt PDFs rendered at the same time. Now your dompdf config is kept: the fonts are cached in its font directory (`storage/fonts` by default), and the package only adds its resources to the chroot and turns off remote resources, PHP and JavaScript.

**Views, styles and translations.**
- The views share one layout, and the `invoices-assets` publish tag is removed. Published views from v3 must be published again.
- Translation changes:
  - New keys: `invoice.credit_invoice`, `invoice.price`, `invoice.direct_debit`, `invoice.paid`, `invoice.credit_paid`, `invoice.nothing_due` and the `format` file.
  - Removed keys: `invoice.amount` (now `price`, "Prijs") and `invoice.unknown`.
  - Changed texts: "Subtotaal excl. btw", ":percentage% btw over :amount", "KvK:", "Document-ID", "Gebruikers-ID", "KvK-nummer" and "Btw-nummer".
  - Published translations keep overriding the texts they contain, so delete the overrides you did not change.
- The PDFs changed:
  - The country of the seller and buyer is shown.
  - The tax is shown per rate with the amount it is calculated over.
  - Columns have fixed widths, so long texts wrap instead of pushing the amounts off the page.
  - The footer sits in the bottom margin of every page, which fits about five lines; longer footers are no longer cut off, but run into the content.
  - Line breaks in notes and item descriptions are kept.
  - Quantities and percentages use the decimal separator of the language.

## Upgrading simplepark-backend

The call sites below are from simplepark-backend at the time of the v4 release.

1. `composer.json`: require `simplepark-bv/laravel-invoices:^4.0`.
2. `app/Actions/Billings/CreatePdfStatement.php`:
   - `->expectedTotal($statement->amount)` now throws on a mismatch instead of logging it. Keep it, so a PDF never shows another amount than the one charged, or catch `ExpectedTotalMismatchException` to keep logging.
   - Replace the custom paid footer (`common.invoice.paid_footer`) with `->paid()` when the statement is paid, and call `->paymentMethod(PaymentMethod::DirectDebit)` when it is collected by direct debit; until now these invoices asked customers to transfer the amount themselves. Zero totals get the "nothing to pay" text automatically.
   - The logo lookup with `file_exists()` can go: the package leaves out a logo it cannot read.
   - For concepts, consider passing `->serial(null)` instead of a serial like `2024.concept`, so the PDF says "Factuur CONCEPT".
3. `app/Actions/CreatePdfUsageStatement.php`: the same footer changes, and the expected total there compares the items with themselves, so it can go.
4. `app/Actions/Concerns/HasBuyer.php`: a `null` name no longer throws a `TypeError`. Receipts render without a name; invoices still require one.
5. Replace `->stream()->getContent()` with `->toPdf()` in `AttachesPdfDocuments`, `StatementPdfService`, `CreateMoneybirdInvoice` and `DownloadPdfUsageReceiptAction`. `stream()` keeps working, so this can wait.
6. `DownloadPdfStatementAction` catches `LogicException`: validation errors are now caught there (they are `InvalidArgumentException`s), render errors are `RenderException`s (`RuntimeException`).
7. `config/invoices.php`: remove `currency`, `decimal_separator`, `thousands_separator` and `precision`. Only `seller` and `logo` differ from the package defaults, so the rest of the file can go too. `default_payment_terms_days`, which the Moneybird code reads, is unchanged.
8. `lang/vendor/invoices`: delete `invoice.php` in both languages (every key equals the v3 package text and would hide the v4 texts), and keep only the parking keys in `usage-receipt.php`: `title`, `filename`, `document_id`, `table.identifier`, `table.category` and `footer.disclaimer`.
9. Tests: `getCustomFooterMessage()` returns `null` instead of `''`, line totals are rounded to cents (amounts with more than 2 decimals throw), and translation keys and texts changed as listed above.
10. Resize `resources/images/logo.png` (2883 × 712 pixels) to about 600 pixels wide: every PDF becomes about 9 times smaller and renders about 6 times faster.

PDFs that were stored before the upgrade keep their old layout.
