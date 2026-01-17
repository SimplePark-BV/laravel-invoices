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
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';
        $invoice->buyer($buyer);

        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 1;
        $item->unitPrice = 10.00;
        $invoice->items([$item]);

        return $invoice;
    }

    protected function create_invoice_item(string $title = 'Test Item', float|int $quantity = 1, float|int $unitPrice = 10.00, ?float $taxPercentage = null): InvoiceItem
    {
        $item = InvoiceItem::make();
        $item->title = $title;
        $item->quantity = $quantity;
        $item->unitPrice = $unitPrice;
        $item->taxPercentage = $taxPercentage;

        return $item;
    }
}
