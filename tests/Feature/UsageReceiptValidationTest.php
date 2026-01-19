<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\ReceiptItem;
use SimpleParkBv\Invoices\Models\UsageReceipt;
use Tests\TestCase;
use Tests\Traits\CreatesTestReceipts;

final class UsageReceiptValidationTest extends TestCase
{
    use CreatesTestReceipts;

    #[Test]
    public function valid_receipt_passes(): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();

        // act & assert
        $this->expectNotToPerformAssertions();
        $receipt->validate();
    }

    #[Test]
    public function missing_buyer_throws_exception(): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $item = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);
        $receipt->items([$item]);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Buyer is required for usage receipt');

        // act
        $receipt->validate();
    }

    #[Test]
    public function empty_items_throws_exception(): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $receipt->buyer($buyer);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Usage receipt must have at least one parking session');

        // act
        $receipt->validate();
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    #[Test]
    #[DataProvider('invalid_item_data_provider')]
    public function invalid_items_throw_exception(array $itemData, string $expectedMessage): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $receipt->buyer($buyer);

        $item = ReceiptItem::make($itemData);
        $receipt->items([$item]);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage($expectedMessage);

        // act
        $receipt->validate();
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalid_item_data_provider(): array
    {
        return [
            'empty user' => [
                [
                    'user' => '',
                    'identifier' => 'ABC-123',
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 12:00:00',
                    'category' => 'Standard Parking',
                    'price' => 5.50,
                ],
                'Item at index 0 must have a user',
            ],
            'empty identifier' => [
                [
                    'user' => 'John Doe',
                    'identifier' => '',
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 12:00:00',
                    'category' => 'Standard Parking',
                    'price' => 5.50,
                ],
                'Item at index 0 must have a identifier',
            ],
            'empty category' => [
                [
                    'user' => 'John Doe',
                    'identifier' => 'ABC-123',
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 12:00:00',
                    'category' => '',
                    'price' => 5.50,
                ],
                'Item at index 0 must have a category',
            ],
            'end date before start date' => [
                [
                    'user' => 'John Doe',
                    'identifier' => 'ABC-123',
                    'start_date' => '2024-01-15 12:00:00',
                    'end_date' => '2024-01-15 10:00:00',
                    'category' => 'Standard Parking',
                    'price' => 5.50,
                ],
                'Item at index 0 end date must be after start date',
            ],
            'negative price' => [
                [
                    'user' => 'John Doe',
                    'identifier' => 'ABC-123',
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 12:00:00',
                    'category' => 'Standard Parking',
                    'price' => -5.50,
                ],
                'Item at index 0 price cannot be negative',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    #[Test]
    #[DataProvider('valid_item_data_provider')]
    public function valid_items_pass_validation(array $itemData): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $receipt->buyer($buyer);

        $item = ReceiptItem::make($itemData);
        $receipt->items([$item]);

        // act & assert
        $this->expectNotToPerformAssertions();
        $receipt->validate();
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function valid_item_data_provider(): array
    {
        return [
            'zero price (free parking)' => [
                [
                    'user' => 'John Doe',
                    'identifier' => 'ABC-123',
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 12:00:00',
                    'category' => 'Free Parking',
                    'price' => 0,
                ],
            ],
            'same start and end date' => [
                [
                    'user' => 'John Doe',
                    'identifier' => 'ABC-123',
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 10:00:00',
                    'category' => 'Standard Parking',
                    'price' => 5.50,
                ],
            ],
            'very long strings in fields' => [
                [
                    'user' => str_repeat('A', 255),
                    'identifier' => str_repeat('B', 255),
                    'start_date' => '2024-01-15 10:00:00',
                    'end_date' => '2024-01-15 12:00:00',
                    'category' => str_repeat('C', 255),
                    'price' => 5.50,
                ],
            ],
        ];
    }

    #[Test]
    public function validation_called_before_rendering(): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        // missing buyer

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Buyer is required for usage receipt');

        // act
        // render() should call validate() internally
        $receipt->render();
    }

    #[Test]
    public function multiple_items_first_invalid(): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $receipt->buyer($buyer);

        $item1 = ReceiptItem::make([
            'user' => '', // invalid: empty user
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);
        $receipt->items([$item1]);

        // assert
        // should throw exception for first invalid item
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Item at index 0 must have a user');

        // act
        $receipt->validate();
    }

    #[Test]
    public function multiple_items_second_invalid(): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $receipt->buyer($buyer);

        $item1 = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);

        $item2 = ReceiptItem::make([
            'user' => 'Jane Doe',
            'identifier' => 'XYZ-789',
            'start_date' => '2024-01-15 16:00:00', // invalid: end before start
            'end_date' => '2024-01-15 14:00:00',
            'category' => 'Premium Parking',
            'price' => 10.00,
        ]);
        $receipt->items([$item1, $item2]);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Item at index 1 end date must be after start date');

        // act
        $receipt->validate();
    }

    #[Test]
    public function all_valid_items_pass(): void
    {
        // arrange
        $receipt = UsageReceipt::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $receipt->buyer($buyer);

        $item1 = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);

        $item2 = ReceiptItem::make([
            'user' => 'Jane Doe',
            'identifier' => 'XYZ-789',
            'start_date' => '2024-01-15 14:00:00',
            'end_date' => '2024-01-15 16:00:00',
            'category' => 'Premium Parking',
            'price' => 10.00,
        ]);

        $item3 = ReceiptItem::make([
            'user' => 'Bob Smith',
            'identifier' => 'DEF-456',
            'start_date' => '2024-01-15 18:00:00',
            'end_date' => '2024-01-15 18:00:00', // same start/end time
            'category' => 'Free Parking',
            'price' => 0,
        ]);
        $receipt->items([$item1, $item2, $item3]);

        // act & assert
        $this->expectNotToPerformAssertions();
        $receipt->validate();
    }
}
