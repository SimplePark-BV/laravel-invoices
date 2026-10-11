<?php

namespace Tests\Traits;

use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\InvoiceItem;

trait CreatesTestInvoices
{
    /**
     * Create a valid invoice: a buyer and one item, without tax.
     *
     * @param  array<string, mixed>  $data
     */
    protected function createInvoice(array $data = []): Invoice
    {
        return Invoice::make([
            'buyer' => ['name' => 'Test Buyer'],
            'items' => [['title' => 'Test Item', 'unit_price' => 10.00]],
            ...$data,
        ]);
    }

    /**
     * Create an issued invoice with every optional section filled: logo, buyer address and contact details,
     * descriptions, two tax rates, an untaxed item and a discount.
     *
     * @param  array<string, mixed>  $data
     */
    protected function createFullInvoice(array $data = []): Invoice
    {
        return Invoice::make([
            'buyer' => [
                'name' => 'Jan de Vries',
                'address' => 'Keizersgracht 123',
                'postal_code' => '1015 CJ',
                'city' => 'Amsterdam',
                'country' => 'Nederland',
                'email' => 'jan@example.test',
                'phone' => '+31 6 12345678',
            ],
            'serial' => '2026.0001',
            'date' => '2026-10-11',
            'logo' => __DIR__.'/../Fixtures/logo.png',
            'items' => [
                ['title' => 'Parking', 'description' => 'Zone 1001, AB-123-C', 'quantity' => 2, 'unit_price' => 12.10, 'tax_percentage' => 21],
                ['title' => 'Parking tax', 'unit_price' => 4.50],
                ['title' => 'Book', 'quantity' => 1.5, 'unit_price' => 14.79, 'tax_percentage' => 9],
                ['title' => 'Discount', 'unit_price' => -1.00, 'tax_percentage' => 21],
            ],
            ...$data,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createInvoiceItem(array $data = []): InvoiceItem
    {
        return InvoiceItem::make([
            'title' => 'Test Item',
            'unit_price' => 10.00,
            ...$data,
        ]);
    }
}
