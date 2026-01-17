<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\InvoiceItem;
use Tests\TestCase;
use Tests\Traits\CreatesTestInvoices;

final class InvoiceValidationTest extends TestCase
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
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 1;
        $item->unit_price = 10.00;
        $invoice->items([$item]);

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
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';
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
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';
        $invoice->buyer($buyer);

        $item = InvoiceItem::make();
        $item->title = $itemData['title'] ?? 'Test Item';
        $item->quantity = $itemData['quantity'] ?? 1;
        $item->unit_price = $itemData['unit_price'] ?? 10.00;
        $item->tax_percentage = $itemData['tax_percentage'] ?? null;
        $invoice->items([$item]);

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
            'negative unit price' => [
                ['title' => 'Test Item', 'quantity' => 1, 'unit_price' => -10.00],
                'Item at index 0 must have a unit_price greater than or equal to 0',
            ],
            'tax percentage less than zero' => [
                ['title' => 'Test Item', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => -1],
                'Item at index 0 must have a tax_percentage between 0 and 100, or null',
            ],
            'tax percentage greater than hundred' => [
                ['title' => 'Test Item', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 101],
                'Item at index 0 must have a tax_percentage between 0 and 100, or null',
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
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';
        $invoice->buyer($buyer);

        $item = InvoiceItem::make();
        $item->title = $itemData['title'];
        $item->quantity = $itemData['quantity'];
        $item->unit_price = $itemData['unit_price'];
        $item->tax_percentage = $itemData['tax_percentage'] ?? null;
        $invoice->items([$item]);

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
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';
        $invoice->buyer($buyer);

        $item1 = InvoiceItem::make();
        $item1->title = ''; // invalid: empty title
        $item1->quantity = 1;
        $item1->unit_price = 10.00;
        $invoice->items([$item1]);

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
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';
        $invoice->buyer($buyer);

        $item1 = InvoiceItem::make();
        $item1->title = 'Valid Item';
        $item1->quantity = 1;
        $item1->unit_price = 10.00;

        $item2 = InvoiceItem::make();
        $item2->title = 'Invalid Item';
        $item2->quantity = 0; // invalid: zero quantity
        $item2->unit_price = 10.00;
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
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';
        $invoice->buyer($buyer);

        $item1 = InvoiceItem::make();
        $item1->title = 'Item 1';
        $item1->quantity = 1;
        $item1->unit_price = 10.00;
        $item1->tax_percentage = 21;

        $item2 = InvoiceItem::make();
        $item2->title = 'Item 2';
        $item2->quantity = 2;
        $item2->unit_price = 20.00;
        $item2->tax_percentage = null;

        $item3 = InvoiceItem::make();
        $item3->title = 'Item 3';
        $item3->quantity = 0.5;
        $item3->unit_price = 0;
        $item3->tax_percentage = 0;
        $invoice->items([$item1, $item2, $item3]);

        // act & assert
        $this->expectNotToPerformAssertions();
        $invoice->validate();
    }
}
