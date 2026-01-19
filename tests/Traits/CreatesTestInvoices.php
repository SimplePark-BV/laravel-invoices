<?php

namespace Tests\Traits;

use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\InvoiceItem;

trait CreatesTestInvoices
{
    protected function create_valid_invoice(): Invoice
    {
        // arrange
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $invoice->buyer($buyer);

        $item = InvoiceItem::make([
            'title' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);
        $invoice->addItem($item);

        return $invoice;
    }

    protected function create_invoice_item(string $title = 'Test Item', float|int $quantity = 1, float|int $unitPrice = 10.00, ?float $taxPercentage = null): InvoiceItem
    {
        return InvoiceItem::make([
            'title' => $title,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_percentage' => $taxPercentage,
        ]);
    }
}
