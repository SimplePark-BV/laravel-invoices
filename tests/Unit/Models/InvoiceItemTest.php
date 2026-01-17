<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException;
use SimpleParkBv\Invoices\Models\InvoiceItem;
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

    /**
     * @return array<string, array{0: string, 1: mixed, 2: string, 3: mixed}>
     */
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

    /**
     * @return array<string, array{0: mixed}>
     */
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

    /**
     * @return array<string, array{0: mixed}>
     */
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
        $total = $item->getTotal();

        // assert
        $this->assertEquals($expected, $total);
    }

    /**
     * @return array<string, array{0: float|int, 1: float|int, 2: float}>
     */
    public static function total_calculation_data_provider(): array
    {
        return [
            'int values' => [3, 10.50, 31.50],
            'float quantity' => [2.5, 10.00, 25.00],
        ];
    }

    #[Test]
    #[DataProvider('boundary_values_data_provider')]
    public function boundary_values_pass_validation(float $taxPercentage): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 1;
        $item->unit_price = 10.00;
        $item->tax_percentage = $taxPercentage;

        // act & assert
        $this->expectNotToPerformAssertions();
        $item->validate();
    }

    /**
     * @return array<string, array{0: float}>
     */
    public static function boundary_values_data_provider(): array
    {
        return [
            'tax percentage 0.01' => [0.01],
            'tax percentage 99.99' => [99.99],
            'tax percentage exactly 0' => [0.0],
            'tax percentage exactly 100' => [100.0],
        ];
    }

    #[Test]
    public function very_small_quantity(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 0.001;
        $item->unit_price = 10.00;

        // act & assert
        $this->expectNotToPerformAssertions();
        $item->validate();
    }

    #[Test]
    public function very_large_unit_price(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 1;
        $item->unit_price = 999999999.99;

        // act
        $total = $item->getTotal();

        // assert
        $this->assertEquals(999999999.99, $total);
    }

    #[Test]
    public function precision_edge_case_calculation(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 0.33;
        $item->unit_price = 3.00;

        // act
        $total = $item->getTotal();

        // assert
        // 0.33 * 3.00 = 0.99 (precision test)
        $this->assertEquals(0.99, $total);
    }

    #[Test]
    #[DataProvider('formatted_tax_percentage_data_provider')]
    public function formatted_tax_percentage(?float $taxPercentage, string $expected): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->tax_percentage = $taxPercentage;

        // act
        $formatted = $item->getFormattedTaxPercentage();

        // assert
        $this->assertEquals($expected, $formatted);
    }

    /**
     * @return array<string, array{0: float|null, 1: string}>
     */
    public static function formatted_tax_percentage_data_provider(): array
    {
        return [
            'with value' => [21, '21%'],
            'with zero' => [0, '0%'],
            'with null' => [null, ''],
            'with decimal' => [9.5, '9.5%'],
        ];
    }

    #[Test]
    public function to_array_includes_all_properties(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->description = 'Test Description';
        $item->quantity = 2;
        $item->unit_price = 10.50;
        $item->tax_percentage = 21;

        // act
        $array = $item->toArray();

        // assert
        $this->assertIsArray($array); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertEquals('Test Item', $array['title']);
        $this->assertEquals('Test Description', $array['description']);
        $this->assertEquals(2, $array['quantity']);
        $this->assertEquals(10.50, $array['unit_price']);
        $this->assertEquals(21, $array['tax_percentage']);
    }

    #[Test]
    #[DataProvider('to_array_null_properties_data_provider')]
    public function to_array_includes_null_for_unset_properties(string $property): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 1;
        $item->unit_price = 10.00;

        // act
        $array = $item->toArray();

        // assert
        $this->assertIsArray($array); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertNull($array[$property]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function to_array_null_properties_data_provider(): array
    {
        return [
            'description null' => ['description'],
            'tax_percentage null' => ['tax_percentage'],
        ];
    }

    #[Test]
    public function validate_passes_with_valid_item(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 1;
        $item->unit_price = 10.00;
        $item->tax_percentage = 21;

        // act & assert
        $this->expectNotToPerformAssertions();
        $item->validate();
    }

    #[Test]
    public function validate_throws_exception_for_empty_title(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = '';
        $item->quantity = 1;
        $item->unit_price = 10.00;

        // assert
        $this->expectException(InvalidInvoiceItemException::class);
        $this->expectExceptionMessage('Item must have a title');

        // act
        $item->validate();
    }

    #[Test]
    public function validate_throws_exception_with_index_prefix(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = '';
        $item->quantity = 1;
        $item->unit_price = 10.00;

        // assert
        $this->expectException(InvalidInvoiceItemException::class);
        $this->expectExceptionMessage('Item at index 5 must have a title');

        // act
        $item->validate(5);
    }

    #[Test]
    public function validate_throws_exception_for_zero_quantity(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 0;
        $item->unit_price = 10.00;

        // assert
        $this->expectException(InvalidInvoiceItemException::class);
        $this->expectExceptionMessage('Item must have a quantity greater than 0');

        // act
        $item->validate();
    }

    #[Test]
    public function validate_throws_exception_for_negative_unit_price(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 1;
        $item->unit_price = -10.00;

        // assert
        $this->expectException(InvalidInvoiceItemException::class);
        $this->expectExceptionMessage('Item must have a unit_price greater than or equal to 0');

        // act
        $item->validate();
    }

    #[Test]
    public function validate_throws_exception_for_invalid_tax_percentage(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->title = 'Test Item';
        $item->quantity = 1;
        $item->unit_price = 10.00;
        $item->tax_percentage = 101;

        // assert
        $this->expectException(InvalidInvoiceItemException::class);
        $this->expectExceptionMessage('Item must have a tax_percentage between 0 and 100, or null');

        // act
        $item->validate();
    }

    #[Test]
    public function description_setter(): void
    {
        // arrange
        $item = InvoiceItem::make();

        // act
        $result = $item->description('Test Description');

        // assert
        $this->assertSame($item, $result);
        $this->assertEquals('Test Description', $item->description);
    }

    #[Test]
    public function description_setter_with_null(): void
    {
        // arrange
        $item = InvoiceItem::make();
        $item->description = 'Existing Description';

        // act
        $result = $item->description(null);

        // assert
        $this->assertSame($item, $result);
        $this->assertNull($item->description);
    }
}
