<?php

namespace Tests\Traits;

use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\InvoiceItem;

trait CreatesTestInvoices
{
    protected function createValidInvoice(): Invoice
    {
        // arrange
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $invoice->buyer($buyer);

        $item = $this->createInvoiceItem();
        $invoice->addItem($item);

        return $invoice;
    }

    protected function createInvoiceItem(string $title = 'Test Item', float|int $quantity = 1, float|int $unitPrice = 10.00, ?float $taxPercentage = null): InvoiceItem
    {
        return InvoiceItem::make([
            'title' => $title,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_percentage' => $taxPercentage,
        ]);
    }
}
