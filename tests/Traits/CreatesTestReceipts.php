<?php

namespace Tests\Traits;

use SimpleParkBv\Invoices\Models\UsageReceipt;
use SimpleParkBv\Invoices\Models\UsageReceiptItem;
use SimpleParkBv\Invoices\Models\UsageReceiptLine;

trait CreatesTestReceipts
{
    /**
     * Create a valid usage receipt: a buyer and one session.
     *
     * @param  array<string, mixed>  $data
     */
    protected function createReceipt(array $data = []): UsageReceipt
    {
        return UsageReceipt::make([
            'buyer' => ['name' => 'Test Buyer'],
            'items' => [$this->receiptItemData()],
            ...$data,
        ]);
    }

    /**
     * Create a usage receipt with every optional section filled: logo, date, ids, buyer details, lines with tax and a note.
     *
     * @param  array<string, mixed>  $data
     */
    protected function createFullReceipt(array $data = []): UsageReceipt
    {
        return UsageReceipt::make([
            'buyer' => [
                'name' => 'Jan de Vries',
                'address' => 'Keizersgracht 123',
                'postal_code' => '1015 CJ',
                'city' => 'Amsterdam',
                'email' => 'jan@example.test',
                'phone' => '+31 6 12345678',
            ],
            'date' => '2026-10-11 14:30',
            'document_id' => 'PS-2026-000123',
            'user_id' => 'USR-000042',
            'logo' => __DIR__.'/../Fixtures/logo.png',
            'items' => [
                $this->receiptItemData(),
                $this->receiptItemData(['identifier' => 'XY-987-Z', 'start_date' => '2026-10-03 10:00', 'end_date' => '2026-10-03 13:30', 'price' => 7.00]),
            ],
            'lines' => [
                ['label' => 'Parking costs', 'amount' => 12.50],
                ['label' => 'Service fee', 'amount' => 0.35, 'tax_percentage' => 21],
                ['label' => 'Discount', 'amount' => -0.10, 'tax_percentage' => 21],
            ],
            'note' => "Thank you for parking.\nSee you next time!",
            ...$data,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createReceiptItem(array $data = []): UsageReceiptItem
    {
        return UsageReceiptItem::make($this->receiptItemData($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createReceiptLine(array $data = []): UsageReceiptLine
    {
        return UsageReceiptLine::make([
            'label' => 'Service fee',
            'amount' => 0.35,
            'tax_percentage' => 21,
            ...$data,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function receiptItemData(array $data = []): array
    {
        return [
            'user' => 'John Doe',
            'identifier' => 'AB-123-C',
            'start_date' => '2026-10-02 10:00',
            'end_date' => '2026-10-02 12:00',
            'category' => 'Zone 1001',
            'price' => 5.50,
            ...$data,
        ];
    }
}
