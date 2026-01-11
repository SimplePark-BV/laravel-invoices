<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException;
use SimpleParkBv\Invoices\InvoiceItem;
use Tests\TestCase;

final class InvoiceItemTest extends TestCase
{
    #[Test]
    public function make_creates_instance(): void
    {
        // arrange
        // act
        $item = InvoiceItem::make();

        // assert
        $this->assertInstanceOf(InvoiceItem::class, $item);
    }

    #[Test]
    #[DataProvider('valid_setter_data_provider')]
    public function setters_with_valid_data(string $method, mixed $value, string $property, mixed $expected): void
    {
        // arrange
        $item = InvoiceItem::make();

        // act
        $result = $item->$method($value);

        // assert
        $this->assertSame($item, $result);
        $this->assertEquals($expected, $item->$property);
    }

    public static function valid_setter_data_provider(): array
    {
        return [
            'title' => ['title', 'Test Item', 'title', 'Test Item'],
            'description' => ['description', 'Test Description', 'description', 'Test Description'],
            'description null' => ['description', null, 'description', null],
            'quantity int' => ['quantity', 5, 'quantity', 5],
            'quantity float' => ['quantity', 2.5, 'quantity', 2.5],
            'unit price' => ['unitPrice', 10.50, 'unit_price', 10.50],
            'unit price zero' => ['unitPrice', 0, 'unit_price', 0],
            'tax percentage' => ['taxPercentage', 21, 'tax_percentage', 21],
            'tax percentage zero' => ['taxPercentage', 0, 'tax_percentage', 0],
            'tax percentage hundred' => ['taxPercentage', 100, 'tax_percentage', 100],
            'tax percentage null' => ['taxPercentage', null, 'tax_percentage', null],
        ];
    }

    #[Test]
    #[DataProvider('invalid_quantity_data_provider')]
    public function quantity_throws_exception_for_invalid_values(mixed $quantity): void
    {
        // arrange
        $item = InvoiceItem::make();

        // assert
        $this->expectException(InvalidInvoiceItemException::class);
        $this->expectExceptionMessage('Quantity must be greater than 0');

        // act
        $item->quantity($quantity);
    }

    public static function invalid_quantity_data_provider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
        ];
    }

    #[Test]
    public function unit_price_throws_exception_for_negative(): void
    {
        // arrange
        $item = InvoiceItem::make();

        // assert
        $this->expectException(InvalidInvoiceItemException::class);
        $this->expectExceptionMessage('Unit price must be greater than or equal to 0');

        // act
        $item->unitPrice(-10.50);
    }

    #[Test]
    #[DataProvider('invalid_tax_percentage_data_provider')]
    public function tax_percentage_throws_exception_for_invalid_values(mixed $taxPercentage): void
    {
        // arrange
        $item = InvoiceItem::make();

        // assert
        $this->expectException(InvalidInvoiceItemException::class);
        $this->expectExceptionMessage('Tax percentage must be between 0 and 100, or null');

        // act
        $item->taxPercentage($taxPercentage);
    }

    public static function invalid_tax_percentage_data_provider(): array
    {
        return [
            'negative' => [-1],
            'greater than hundred' => [101],
        ];
    }

    #[Test]
    #[DataProvider('total_calculation_data_provider')]
    public function total_calculation(float|int $quantity, float|int $unitPrice, float $expected): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->quantity = $quantity;
        $item->unit_price = $unitPrice;

        // act
        $total = $item->total();

        // assert
        $this->assertEquals($expected, $total);
    }

    public static function total_calculation_data_provider(): array
    {
        return [
            'int values' => [3, 10.50, 31.50],
            'float quantity' => [2.5, 10.00, 25.00],
        ];
    }

    #[Test]
    #[DataProvider('formatted_tax_percentage_data_provider')]
    public function formatted_tax_percentage(?float $taxPercentage, string $expected): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->tax_percentage = $taxPercentage;

        // act
        $formatted = $item->formattedTaxPercentage();

        // assert
        $this->assertEquals($expected, $formatted);
    }

    public static function formatted_tax_percentage_data_provider(): array
    {
        return [
            'with value' => [21, '21%'],
            'with zero' => [0, '0%'],
            'with null' => [null, ''],
            'with decimal' => [9.5, '9.5%'],
        ];
    }
}
