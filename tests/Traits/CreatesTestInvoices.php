<?php

namespace Tests\Traits;

use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\InvoiceItem;

trait CreatesTestInvoices
{
    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function createValidInvoice(array $attrs = []): Invoice
    {
        $invoice = Invoice::make($attrs);

        $invoice->buyer(
            Buyer::make(['name' => 'Test Buyer'])
        );

        $invoice->addItem(
            $this->createInvoiceItem()
        );

        return $invoice;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function createInvoiceItem(array $attrs = []): InvoiceItem
    {
        return InvoiceItem::make([
            'title' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 10.00,
            'tax_percentage' => null,
            ...$attrs,
        ]);
    }

    /**
     * Create an invoice with buyer, item, and optional configuration.
     *
     * @param  array<string, mixed>  $attrs  Invoice properties (series, sequence, date, expected_total, items, etc.)
     * @param  float|null  $actualTotal  Optional: set item unit_price to this value (overrides items[0].unit_price)
     * @param  bool  $throw  Whether to throw exception on expected total mismatch (default: false)
     */
    protected function createInvoiceWithTotal(array $attrs, ?float $actualTotal = null, bool $throw = false): Invoice
    {
        // build attrs with defaults
        $invoiceAttrs = [
            'buyer' => ['name' => 'Test Buyer'],
            ...$attrs,
            'items' => [[
                'title' => 'Test Item',
                'quantity' => 1,
                'unit_price' => $actualTotal ?? 10.00,
                'tax_percentage' => null,
            ]],
        ];

        $invoice = Invoice::make($invoiceAttrs);

        // handle expected_total separately if set
        if (isset($invoiceAttrs['expected_total']) && is_numeric($invoiceAttrs['expected_total'])) {
            $invoice->expectedTotal((float) $invoiceAttrs['expected_total'], $throw);
        }

        return $invoice;
    }
}
