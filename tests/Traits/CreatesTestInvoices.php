<?php

namespace Tests\Traits;

use SimpleParkBv\Invoices\Buyer;
use SimpleParkBv\Invoices\Invoice;
use SimpleParkBv\Invoices\InvoiceItem;

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
        $item->unit_price = 10.00;
        $invoice->addItem($item);

        return $invoice;
    }

    protected function create_invoice_item(string $title = 'Test Item', float|int $quantity = 1, float|int $unitPrice = 10.00, ?float $taxPercentage = null): InvoiceItem
    {
        $item = InvoiceItem::make();
        $item->title = $title;
        $item->quantity = $quantity;
        $item->unit_price = $unitPrice;
        $item->tax_percentage = $taxPercentage;

        return $item;
    }
}
