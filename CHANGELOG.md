# Changelog

## v4.1.1

### Changed

- The tax below the total of a usage receipt reads "21% VAT" / "21% btw" instead of "Of which 21% VAT" / "Waarvan 21% btw".
- With `itemPrices(false)`, the last column of the sessions is right-aligned, so the table no longer ends in blank space.

## v4.1.0

### Added

- `UsageReceipt::itemPrices(false)` hides the price column of the sessions, e.g. when the lines already break the total down.
- Countries given as a two-letter ISO code are shown by their name in the document language (with the intl extension).

### Changed

- The buyer's country is left out when it is the seller's country.

## v4.0.0

A rewrite of the internals after a review of the package. See the [upgrade guide](UPGRADE.md) for every breaking change.

### Fixed

- Concurrent renders could embed truncated fonts: the package replaced the dompdf options, so dompdf wrote the fonts into `vendor/` again on every render. The app's dompdf config is now kept, so the fonts are cached once in its font directory, and rendering no longer fails when `vendor/` is read-only.
- Outputting the same document twice returned a corrupt second PDF, and changes after a first output were ignored. Every output now renders the current document.
- Invoices numbered with `series()` and `sequence()` downloaded as `factuur-CONCEPT.pdf`, and a `/` in the number made the download throw.
- Printed line totals, subtotals and tax did not always add up, because of float math. All amounts are now calculated in whole cents, with the tax rounded once per rate.
- `getTaxAmount()` and `getSubTotal()` differed by a cent from the PDF for some invoices with several rates.
- The expected total check ignored some one-cent differences and caught others, depending on float noise.
- A total of zero asked the buyer to pay € 0,00, or, with float noise, promised to transfer it.
- `make($document->toArray())` threw a `TypeError`, and lost the footer.
- `make()` silently ignored misspelled keys, and called any public method named like a key, such as `download`.
- Rates such as 9.5% were printed as "9% btw" on invoices, and quantities and percentages used a decimal point in Dutch.
- English documents used Dutch number formatting.
- The tax of receipt lines was printed as if it was added to the total.
- Long texts without spaces pushed the amounts off the page, and footers longer than three lines were cut off.
- A buyer without a name crashed rendering with a `TypeError`.
- Dates passed as `CarbonImmutable` or `DateTime` lost their timezone or threw, an empty date meant "now", and the date objects of the caller were shared and mutable.
- Sessions ending at the moment they started failed validation.
- Languages added by publishing the translations were rejected.
- Rendering reset the translator and Carbon locale of the app.
- Relative logo paths depended on the working directory, SVG logos only worked on PHP 8.5, and a missing logo drew a grey box.
- A font name with a quote broke the stylesheet.
- Render failures of usage receipts threw an `InvalidInvoiceException`.

### Added

- `toPdf()` and `toHtml()` on documents.
- `getTotals()`, with the totals in cents and the tax per rate.
- `paymentMethod()` (bank transfer or direct debit) and `paid()`, each with their own footer text, and a "nothing to pay" text for a total of zero.
- Credit invoices: a negative total is titled "Creditfactuur" / "Credit invoice".
- The amount the tax is calculated over, per rate, on invoices.
- `dueDate()`, `seller()`, `Seller::fromConfig()`, and setters for every seller field.
- Exceptions: the `InvoicesException` interface, `InvalidDocumentException`, `ExpectedTotalMismatchException`, `UnsupportedLanguageException` and `RenderException`.
- Config: `timezone`, `date_format`, `datetime_format` and `seller.phone`.
- Support for Laravel 13.

### Changed

- `expectedTotal()` throws on any mismatch.
- Amounts have at most 2 decimals.
- Array keys are strict.
- Dates are stored as `CarbonImmutable` and shown in the display timezone, with month names in the document language.
- Number formats follow the document language.
- The seller config has no placeholder defaults.
- Views share one layout, and texts were corrected ("KvK-nummer", "Btw-nummer", "Prijs").
- PHPStan runs at the maximum level, and the tests render real PDFs in both languages.

### Removed

- `render()`, `isRendered()`, `clearPdf()` and the public rendering properties.
- The document, item and party interfaces.
- The separate total getters, `taxRate()`, `Buyer::fromParty()` and the item date formats.
- The `currency`, `decimal_separator`, `thousands_separator` and `precision` config keys, and the `invoices-assets` publish tag.
- Support for PHP 8.2 and Laravel 11.
