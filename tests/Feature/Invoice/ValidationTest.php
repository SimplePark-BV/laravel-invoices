<?php

namespace Tests\Feature\Invoice;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\InvoiceItem;
use Tests\TestCase;
use Tests\Traits\CreatesTestInvoices;

final class ValidationTest extends TestCase
{
    use CreatesTestInvoices;

    #[Test]
    public function valid_invoice_passes(): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();

        // act & assert
        $this->expectNotToPerformAssertions();
        $invoice->validate();
    }

    #[Test]
    public function missing_buyer_throws_exception(): void
    {
        // arrange
        $invoice = Invoice::make();
        $item = InvoiceItem::make([
            'title' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);

        $invoice->addItem($item);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Buyer is required for invoice');

        // act
        $invoice->validate();
    }

    #[Test]
    public function empty_items_throws_exception(): void
    {
        // arrange
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $invoice->buyer($buyer);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Invoice must have at least one item');

        // act
        $invoice->validate();
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    #[Test]
    #[DataProvider('invalid_item_data_provider')]
    public function invalid_items_throw_exception(array $itemData, string $expectedMessage): void
    {
        // arrange
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $invoice->buyer($buyer);

        $item = InvoiceItem::make($itemData);

        $invoice->addItem($item);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage($expectedMessage);

        // act
        $invoice->validate();
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalid_item_data_provider(): array
    {
        return [
            'empty title' => [
                ['title' => '', 'quantity' => 1, 'unit_price' => 10.00],
                'Item at index 0 must have a title',
            ],
            'zero quantity' => [
                ['title' => 'Test Item', 'quantity' => 0, 'unit_price' => 10.00],
                'Item at index 0 must have a quantity greater than 0',
            ],
            'negative quantity' => [
                ['title' => 'Test Item', 'quantity' => -1, 'unit_price' => 10.00],
                'Item at index 0 must have a quantity greater than 0',
            ],
            'tax percentage less than zero' => [
                ['title' => 'Test Item', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => -1],
                'Item at index 0 must have a taxPercentage between 0 and 100, or null',
            ],
            'tax percentage greater than hundred' => [
                ['title' => 'Test Item', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 101],
                'Item at index 0 must have a taxPercentage between 0 and 100, or null',
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
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $invoice->buyer($buyer);

        $item = InvoiceItem::make($itemData);
        $invoice->addItem($item);

        // act & assert
        $this->expectNotToPerformAssertions();
        $invoice->validate();
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function valid_item_data_provider(): array
    {
        return [
            'zero unit price' => [
                ['title' => 'Test Item', 'quantity' => 1, 'unit_price' => 0],
            ],
            'negative unit price (discount)' => [
                ['title' => 'Discount', 'quantity' => 1, 'unit_price' => -10.00],
            ],
            'null tax percentage' => [
                ['title' => 'Test Item', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => null],
            ],
            'valid tax percentage' => [
                ['title' => 'Test Item', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 21],
            ],
            'tax percentage zero' => [
                ['title' => 'Test Item', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 0],
            ],
            'tax percentage hundred' => [
                ['title' => 'Test Item', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 100],
            ],
        ];
    }

    #[Test]
    public function validation_called_before_rendering(): void
    {
        // arrange
        $invoice = Invoice::make();
        // missing buyer

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Buyer is required for invoice');

        // act
        // render() should call validate() internally
        $invoice->render();
    }

    #[Test]
    public function multiple_items_first_invalid(): void
    {
        // arrange
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $invoice->buyer($buyer);

        $item1 = InvoiceItem::make([
            'title' => '', // invalid: empty title
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);
        $invoice->addItem($item1);

        // assert
        // should throw exception for first invalid item
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Item at index 0 must have a title');

        // act
        $invoice->validate();
    }

    #[Test]
    public function multiple_items_second_invalid(): void
    {
        // arrange
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $invoice->buyer($buyer);

        $item1 = InvoiceItem::make([
            'title' => 'Valid Item',
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);

        $item2 = InvoiceItem::make([
            'title' => 'Invalid Item',
            'quantity' => 0, // invalid: zero quantity
            'unit_price' => 10.00,
        ]);
        $invoice->items([$item1, $item2]);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Item at index 1 must have a quantity greater than 0');

        // act
        $invoice->validate();
    }

    #[Test]
    public function all_valid_items_pass(): void
    {
        // arrange
        $invoice = Invoice::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $invoice->buyer($buyer);

        $item1 = InvoiceItem::make([
            'title' => 'Item 1',
            'quantity' => 1,
            'unit_price' => 10.00,
            'tax_percentage' => 21,
        ]);

        $item2 = InvoiceItem::make([
            'title' => 'Item 2',
            'quantity' => 2,
            'unit_price' => 20.00,
            'tax_percentage' => null,
        ]);

        $item3 = InvoiceItem::make([
            'title' => 'Item 3',
            'quantity' => 0.5,
            'unit_price' => 0,
            'tax_percentage' => 0,
        ]);
        $invoice->items([$item1, $item2, $item3]);

        // act & assert
        $this->expectNotToPerformAssertions();
        $invoice->validate();
    }
}
