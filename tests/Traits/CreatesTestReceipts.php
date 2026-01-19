<?php

namespace Tests\Traits;

use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\ReceiptItem;
use SimpleParkBv\Invoices\Models\UsageReceipt;

trait CreatesTestReceipts
{
    protected function create_valid_receipt(): UsageReceipt
    {
        // arrange
        $receipt = UsageReceipt::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $receipt->buyer($buyer);

        $item = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);
        $receipt->items([$item]);

        return $receipt;
    }

    protected function create_receipt_item(
        string $user = 'John Doe',
        string $identifier = 'ABC-123',
        string $startDate = '2024-01-15 10:00:00',
        string $endDate = '2024-01-15 12:00:00',
        string $category = 'Standard Parking',
        float $price = 5.50
    ): ReceiptItem {
        return ReceiptItem::make([
            'user' => $user,
            'identifier' => $identifier,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'category' => $category,
            'price' => $price,
        ]);
    }
}
