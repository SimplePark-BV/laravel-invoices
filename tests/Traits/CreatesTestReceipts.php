<?php

namespace Tests\Traits;

use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\UsageReceipt;
use SimpleParkBv\Invoices\Models\UsageReceiptItem;

trait CreatesTestReceipts
{
    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function createValidReceipt(array $attrs = []): UsageReceipt
    {
        // arrange
        $receipt = UsageReceipt::make($attrs);

        $receipt->buyer(
            Buyer::make(['name' => 'Test Buyer'])
        );

        $receipt->addItem(
            $this->createReceiptItem()
        );

        return $receipt;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function createReceiptItem(array $attrs = []): UsageReceiptItem
    {
        return UsageReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
            ...$attrs,
        ]);
    }
}
