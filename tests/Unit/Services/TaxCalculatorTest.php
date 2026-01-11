<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\InvoiceItem;
use SimpleParkBv\Invoices\Services\TaxCalculator;
use Tests\TestCase;

final class TaxCalculatorTest extends TestCase
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    #[Test]
    #[DataProvider('calculate_tax_amount_data_provider')]
    public function calculate_tax_amount(array $items, float $expected): void
    {
        // arrange
        $itemsCollection = collect(array_map(function ($itemData) {
            $item = InvoiceItem::make();
            $item->title = $itemData['title'];
            $item->quantity = $itemData['quantity'];
            $item->unit_price = $itemData['unit_price'];
            $item->tax_percentage = $itemData['tax_percentage'] ?? null;

            return $item;
        }, $items));

        // act
        $taxAmount = TaxCalculator::calculateTaxAmount($itemsCollection);

        // assert
        $this->assertEquals($expected, round($taxAmount, 2));
    }

    /**
     * @return array<string, array{0: array<int, array<string, mixed>>, 1: float}>
     */
    public static function calculate_tax_amount_data_provider(): array
    {
        return [
            'single item' => [
                [['title' => 'Item', 'quantity' => 1, 'unit_price' => 121.00, 'tax_percentage' => 21]],
                21.00, // tax = 121 * 0.21 / 1.21 = 21
            ],
            'multiple items' => [
                [
                    ['title' => 'Item 1', 'quantity' => 1, 'unit_price' => 121.00, 'tax_percentage' => 21],
                    ['title' => 'Item 2', 'quantity' => 1, 'unit_price' => 110.00, 'tax_percentage' => 10],
                ],
                31.00, // tax1 = 21, tax2 = 10, total = 31
            ],
            'null tax percentage' => [
                [['title' => 'Item', 'quantity' => 1, 'unit_price' => 100.00, 'tax_percentage' => null]],
                0,
            ],
            'zero tax percentage' => [
                [['title' => 'Item', 'quantity' => 1, 'unit_price' => 100.00, 'tax_percentage' => 0]],
                0,
            ],
            'negative tax percentage' => [
                [['title' => 'Item', 'quantity' => 1, 'unit_price' => 100.00, 'tax_percentage' => -5]],
                0,
            ],
            'mixed scenarios' => [
                [
                    ['title' => 'Item 1', 'quantity' => 1, 'unit_price' => 121.00, 'tax_percentage' => 21],
                    ['title' => 'Item 2', 'quantity' => 1, 'unit_price' => 100.00, 'tax_percentage' => null],
                    ['title' => 'Item 3', 'quantity' => 2, 'unit_price' => 110.00, 'tax_percentage' => 10],
                ],
                41.00, // tax1 = 21, tax2 = 0, tax3 = 220 * 0.10 / 1.10 = 20, total = 41
            ],
            'quantity greater than one' => [
                [['title' => 'Item', 'quantity' => 3, 'unit_price' => 121.00, 'tax_percentage' => 21]],
                63.00, // 363 total, tax = 363 * 0.21 / 1.21 = 63
            ],
            'empty collection' => [
                [],
                0.00,
            ],
            'very large numbers' => [
                [['title' => 'Item', 'quantity' => 1, 'unit_price' => 999999999.99, 'tax_percentage' => 21]],
                173553719.01, // tax = 999999999.99 * 0.21 / 1.21 ≈ 173553719.01
            ],
            'precision edge case' => [
                [['title' => 'Item', 'quantity' => 0.33, 'unit_price' => 121.00, 'tax_percentage' => 21]],
                6.93, // 39.93 total, tax = 39.93 * 0.21 / 1.21 ≈ 6.93
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, float>  $expectedGroups
     */
    #[Test]
    #[DataProvider('extract_tax_groups_data_provider')]
    public function extract_tax_groups(array $items, array $expectedGroups, int $expectedCount): void
    {
        // arrange
        $itemsCollection = collect(array_map(function ($itemData) {
            $item = InvoiceItem::make();
            $item->title = $itemData['title'];
            $item->quantity = $itemData['quantity'];
            $item->unit_price = $itemData['unit_price'];
            $item->tax_percentage = $itemData['tax_percentage'] ?? null;

            return $item;
        }, $items));

        // act
        $taxGroups = TaxCalculator::extractTaxGroups($itemsCollection);

        // assert
        $this->assertInstanceOf(Collection::class, $taxGroups);
        $this->assertCount($expectedCount, $taxGroups);
        foreach ($expectedGroups as $expectedGroup) {
            $this->assertContains($expectedGroup, $taxGroups);
        }
    }

    /**
     * @return array<string, array{0: array<int, array<string, mixed>>, 1: array<int, float>, 2: int}>
     */
    public static function extract_tax_groups_data_provider(): array
    {
        return [
            'unique tax percentages' => [
                [
                    ['title' => 'Item 1', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 21],
                    ['title' => 'Item 2', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 9],
                    ['title' => 'Item 3', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 21],
                ],
                [21.0, 9.0],
                2,
            ],
            'excludes null values' => [
                [
                    ['title' => 'Item 1', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 21],
                    ['title' => 'Item 2', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => null],
                ],
                [21.0],
                1,
            ],
            'excludes zero and negative' => [
                [
                    ['title' => 'Item 1', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 21],
                    ['title' => 'Item 2', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => 0],
                    ['title' => 'Item 3', 'quantity' => 1, 'unit_price' => 10.00, 'tax_percentage' => -5],
                ],
                [21.0],
                1,
            ],
        ];
    }

    #[Test]
    public function extract_tax_groups_sorted_descending(): void
    {
        // arrange
        $items = collect([
            $this->create_item('Item 1', 1, 10.00, 9),
            $this->create_item('Item 2', 1, 10.00, 21),
            $this->create_item('Item 3', 1, 10.00, 15),
        ]);

        // act
        $taxGroups = TaxCalculator::extractTaxGroups($items);

        // assert
        $this->assertEquals(21.0, $taxGroups->get(0));
        $this->assertEquals(15.0, $taxGroups->get(1));
        $this->assertEquals(9.0, $taxGroups->get(2));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    #[Test]
    #[DataProvider('calculate_tax_for_group_data_provider')]
    public function calculate_tax_for_group(array $items, float $taxPercentage, float $expected): void
    {
        // arrange
        $itemsCollection = collect(array_map(function ($itemData) {
            return $this->create_item($itemData['title'], $itemData['quantity'], $itemData['unit_price'], $itemData['tax_percentage'] ?? null);
        }, $items));

        // act
        $tax = TaxCalculator::calculateTaxForGroup($itemsCollection, $taxPercentage);

        // assert
        $this->assertEquals($expected, round($tax, 2));
    }

    /**
     * @return array<string, array{0: array<int, array<string, mixed>>, 1: float, 2: float}>
     */
    public static function calculate_tax_for_group_data_provider(): array
    {
        return [
            'filters by tax percentage' => [
                [
                    ['title' => 'Item 1', 'quantity' => 1, 'unit_price' => 121.00, 'tax_percentage' => 21],
                    ['title' => 'Item 2', 'quantity' => 1, 'unit_price' => 110.00, 'tax_percentage' => 10],
                    ['title' => 'Item 3', 'quantity' => 1, 'unit_price' => 121.00, 'tax_percentage' => 21],
                ],
                21,
                42.00, // tax21 = (121 + 121) * 0.21 / 1.21 = 42
            ],
            'returns zero for non matching' => [
                [['title' => 'Item', 'quantity' => 1, 'unit_price' => 121.00, 'tax_percentage' => 21]],
                10,
                0,
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    #[Test]
    #[DataProvider('calculate_subtotal_for_tax_group_data_provider')]
    public function calculate_subtotal_for_tax_group(array $items, float $taxPercentage, float $expected): void
    {
        // arrange
        $itemsCollection = collect(array_map(function ($itemData) {
            return $this->create_item($itemData['title'], $itemData['quantity'], $itemData['unit_price'], $itemData['tax_percentage'] ?? null);
        }, $items));

        // act
        $subTotal = TaxCalculator::calculateSubTotalForTaxGroup($itemsCollection, $taxPercentage);

        // assert
        $this->assertEquals($expected, round($subTotal, 2));
    }

    /**
     * @return array<string, array{0: array<int, array<string, mixed>>, 1: float, 2: float}>
     */
    public static function calculate_subtotal_for_tax_group_data_provider(): array
    {
        return [
            'single tax group' => [
                [
                    ['title' => 'Item 1', 'quantity' => 1, 'unit_price' => 121.00, 'tax_percentage' => 21],
                    ['title' => 'Item 2', 'quantity' => 1, 'unit_price' => 121.00, 'tax_percentage' => 21],
                ],
                21,
                200.00, // itemsTotal = 242, taxAmount = 42, subtotal = 200
            ],
            'multiple tax groups' => [
                [
                    ['title' => 'Item 1', 'quantity' => 1, 'unit_price' => 121.00, 'tax_percentage' => 21],
                    ['title' => 'Item 2', 'quantity' => 1, 'unit_price' => 110.00, 'tax_percentage' => 10],
                ],
                21,
                100.00, // subtotal21 = 121 - 21 = 100
            ],
        ];
    }

    private function create_item(string $title, float|int $quantity, float|int $unitPrice, ?float $taxPercentage): InvoiceItem
    {
        $item = InvoiceItem::make();
        $item->title = $title;
        $item->quantity = $quantity;
        $item->unit_price = $unitPrice;
        $item->tax_percentage = $taxPercentage;

        return $item;
    }

    #[Test]
    public function calculate_tax_amount_with_empty_collection(): void
    {
        // arrange
        $items = collect([]);

        // act
        $taxAmount = TaxCalculator::calculateTaxAmount($items);

        // assert
        $this->assertEquals(0.00, $taxAmount);
    }

    #[Test]
    public function extract_tax_groups_with_empty_collection(): void
    {
        // arrange
        $items = collect([]);

        // act
        $taxGroups = TaxCalculator::extractTaxGroups($items);

        // assert
        $this->assertInstanceOf(Collection::class, $taxGroups);
        $this->assertCount(0, $taxGroups);
    }
}
