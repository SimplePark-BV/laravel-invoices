<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Models\InvoiceItem;
use Tests\TestCase;
use Tests\Traits\CreatesTestInvoices;

final class InvoiceItemTest extends TestCase
{
    use CreatesTestInvoices;

    #[Test]
    public function make_fills_the_item_from_an_array(): void
    {
        // act
        $item = InvoiceItem::make([
            'title' => 'Parking',
            'description' => 'Zone 1001',
            'quantity' => 2,
            'unit_price' => 12.10,
            'tax_percentage' => 21,
        ]);

        // assert
        $this->assertSame('Parking', $item->getTitle());
        $this->assertSame('Zone 1001', $item->getDescription());
        $this->assertSame(2, $item->getQuantity());
        $this->assertSame(12.10, $item->getUnitPrice());
        $this->assertSame(1210, $item->getUnitPriceCents());
        $this->assertSame(21.0, $item->getTaxPercentage());
    }

    #[Test]
    public function make_accepts_camel_case_keys(): void
    {
        // act
        $item = InvoiceItem::make(['title' => 'Parking', 'unitPrice' => 12.10, 'taxPercentage' => 21]);

        // assert
        $this->assertSame(1210, $item->getUnitPriceCents());
        $this->assertSame(21.0, $item->getTaxPercentage());
    }

    #[Test]
    public function make_rejects_unknown_keys(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage("Unknown key 'price' for InvoiceItem");

        // act
        InvoiceItem::make(['title' => 'Parking', 'price' => 12.10]);
    }

    #[Test]
    public function make_rejects_values_of_the_wrong_type(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage("Invalid value for 'title' of InvoiceItem: array");

        // act
        InvoiceItem::make(['title' => ['Parking']]);
    }

    #[Test]
    public function the_quantity_is_one_by_default(): void
    {
        // act & assert
        $this->assertSame(1, InvoiceItem::make()->getQuantity());
    }

    #[Test]
    public function quantity_keeps_fractions_and_turns_whole_numbers_into_integers(): void
    {
        // act & assert
        $this->assertSame(1.5, InvoiceItem::make(['quantity' => 1.5])->getQuantity());
        $this->assertSame(3, InvoiceItem::make(['quantity' => 3.0])->getQuantity());
        $this->assertSame(2, InvoiceItem::make(['quantity' => '2'])->getQuantity());
    }

    #[Test]
    public function texts_are_trimmed_and_empty_texts_become_null(): void
    {
        // act
        $item = InvoiceItem::make(['title' => '  Parking  ', 'description' => '   ']);

        // assert
        $this->assertSame('Parking', $item->getTitle());
        $this->assertNull($item->getDescription());
    }

    #[Test]
    #[DataProvider('totals')]
    public function the_total_is_rounded_to_whole_cents(float $unitPrice, float|int $quantity, int $expectedCents): void
    {
        // arrange
        $item = $this->createInvoiceItem(['unit_price' => $unitPrice, 'quantity' => $quantity]);

        // act & assert
        $this->assertSame($expectedCents, $item->getTotalCents());
        $this->assertSame($expectedCents / 100.0, $item->getTotal());
    }

    /**
     * @return array<string, array{float, float|int, int}>
     */
    public static function totals(): array
    {
        return [
            'whole quantity' => [12.10, 2, 2420],
            'fractional quantity rounds half up' => [14.79, 2.5, 3698],
            'discount' => [-1.00, 1, -100],
            'half a cent' => [0.33, 1.5, 50],
        ];
    }

    #[Test]
    public function unit_price_rejects_more_than_two_decimals(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('The unit price must have at most 2 decimals');

        // act
        $this->createInvoiceItem(['unit_price' => 33.333]);
    }

    #[Test]
    public function unit_price_rejects_text_that_is_not_a_number(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);

        // act
        $this->createInvoiceItem(['unit_price' => '12,50']);
    }

    #[Test]
    public function tax_percentage_rejects_text_that_is_not_a_number(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);

        // act
        $this->createInvoiceItem(['tax_percentage' => 'high']);
    }

    #[Test]
    public function a_valid_item_passes_validation(): void
    {
        // arrange
        $item = $this->createInvoiceItem(['tax_percentage' => 21]);

        // act
        $item->validate();

        // assert
        $this->addToAssertionCount(1);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Test]
    #[DataProvider('invalidItems')]
    public function validate_rejects_invalid_items(array $data, string $message): void
    {
        // arrange
        $item = InvoiceItem::make($data);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage($message);

        // act
        $item->validate(3);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidItems(): array
    {
        return [
            'no title' => [['unit_price' => 10], 'Item at index 3 must have a title'],
            'blank title' => [['title' => '   ', 'unit_price' => 10], 'Item at index 3 must have a title'],
            'no unit price' => [['title' => 'Parking'], 'Item at index 3 must have a unit price'],
            'zero quantity' => [['title' => 'Parking', 'unit_price' => 10, 'quantity' => 0], 'Item at index 3 must have a quantity greater than 0'],
            'negative quantity' => [['title' => 'Parking', 'unit_price' => 10, 'quantity' => -1], 'Item at index 3 must have a quantity greater than 0'],
            'negative tax' => [['title' => 'Parking', 'unit_price' => 10, 'tax_percentage' => -1], 'Item at index 3 must have a tax percentage between 0 and 100, or null'],
            'tax above 100' => [['title' => 'Parking', 'unit_price' => 10, 'tax_percentage' => 100.01], 'Item at index 3 must have a tax percentage between 0 and 100, or null'],
        ];
    }

    #[Test]
    public function a_title_of_zero_is_valid(): void
    {
        // arrange
        $item = $this->createInvoiceItem(['title' => '0']);

        // act
        $item->validate();

        // assert
        $this->assertSame('0', $item->getTitle());
    }

    #[Test]
    public function to_array_returns_the_fields(): void
    {
        // arrange
        $item = $this->createInvoiceItem(['description' => 'Zone 1001', 'quantity' => 1.5, 'tax_percentage' => 9]);

        // act & assert
        $this->assertSame([
            'title' => 'Test Item',
            'description' => 'Zone 1001',
            'quantity' => 1.5,
            'unit_price' => 10.0,
            'tax_percentage' => 9.0,
        ], $item->toArray());
    }
}
