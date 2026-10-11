<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Models\InvoiceItem;
use SimpleParkBv\Invoices\Models\TaxGroup;
use SimpleParkBv\Invoices\Services\TaxCalculator;
use Tests\TestCase;

final class TaxCalculatorTest extends TestCase
{
    #[Test]
    #[DataProvider('includedTaxes')]
    public function included_tax_rounds_half_away_from_zero(int $grossCents, int $basisPoints, int $expected): void
    {
        // act & assert
        $this->assertSame($expected, TaxCalculator::includedTax($grossCents, $basisPoints));
    }

    /**
     * @return array<string, array{int, int, int}>
     */
    public static function includedTaxes(): array
    {
        return [
            '21% of 12.10' => [1210, 2100, 210],
            '21% of 0.35' => [35, 2100, 6],
            '9% of 36.98' => [3698, 900, 305],
            '9.5% of 10.95' => [1095, 950, 95],
            'exactly half a cent rounds up' => [5, 10000, 3],
            'negative amount is mirrored' => [-1210, 2100, -210],
            'negative half a cent rounds down' => [-5, 10000, -3],
            'zero rate' => [1210, 0, 0],
            'zero amount' => [0, 2100, 0],
            'amount too large for integer arithmetic' => [PHP_INT_MAX >> 10, 2100, (int) round((PHP_INT_MAX >> 10) * 2100 / 12100)],
        ];
    }

    #[Test]
    public function calculate_groups_the_tax_per_rate_highest_first(): void
    {
        // arrange
        $items = [
            $this->item(10.00, 9),
            $this->item(12.10, 21),
            $this->item(4.50, null),
            $this->item(-1.00, 21),
            $this->item(5.00, 0),
        ];

        // act
        $totals = TaxCalculator::calculate($items);

        // assert
        $this->assertSame(3060, $totals->getTotalCents());
        $this->assertSame(
            [[21.0, 1110, 193], [9.0, 1000, 83], [0.0, 500, 0]],
            array_map(static fn (TaxGroup $group): array => [$group->getPercentage(), $group->getGrossCents(), $group->getTaxCents()], $totals->getTaxGroups()),
        );
        $this->assertSame(276, $totals->getTaxCents());
        $this->assertSame(2784, $totals->getSubtotalCents());
    }

    #[Test]
    public function calculate_groups_equal_rates_with_float_noise_together(): void
    {
        // arrange: 0.21 * 100 is 21.000000000000004 in floating point
        $items = [$this->item(12.10, 0.21 * 100), $this->item(12.10, 21)];

        // act
        $totals = TaxCalculator::calculate($items);

        // assert
        $this->assertCount(1, $totals->getTaxGroups());
        $this->assertSame(420, $totals->getTaxCents());
    }

    #[Test]
    public function calculate_without_lines_is_zero(): void
    {
        // act
        $totals = TaxCalculator::calculate([]);

        // assert
        $this->assertSame(0, $totals->getTotalCents());
        $this->assertSame([], $totals->getTaxGroups());
    }

    #[Test]
    public function the_printed_breakdown_always_adds_up(): void
    {
        // arrange: random invoices with fractional quantities, discounts and mixed rates
        mt_srand(2026);

        for ($run = 0; $run < 500; $run++) {
            $items = [];

            for ($line = 0, $lines = mt_rand(1, 8); $line < $lines; $line++) {
                $rates = [null, 0, 9, 21, 9.5];
                $items[] = InvoiceItem::make([
                    'title' => 'Item',
                    'unit_price' => mt_rand(-2000, 50000) / 100,
                    'quantity' => [1, 2, 3, 0.5, 1.5, 2.25][mt_rand(0, 5)],
                    'tax_percentage' => $rates[mt_rand(0, 4)],
                ]);
            }

            // act
            $totals = TaxCalculator::calculate($items);

            // assert: the line totals add up to the total, and the subtotal plus the tax of every group is the total
            $this->assertSame(array_sum(array_map(static fn (InvoiceItem $item): int => $item->getTotalCents(), $items)), $totals->getTotalCents());
            $this->assertSame($totals->getTotalCents(), $totals->getSubtotalCents() + $totals->getTaxCents());

            foreach ($totals->getTaxGroups() as $group) {
                $exact = $group->getGrossCents() * $group->getPercentage() / (100 + $group->getPercentage());
                $this->assertEqualsWithDelta($exact, $group->getTaxCents(), 0.5 + 1e-9);
                $this->assertSame($group->getGrossCents(), $group->getNetCents() + $group->getTaxCents());
            }
        }
    }

    #[Test]
    #[DataProvider('basisPoints')]
    public function to_basis_points_converts_percentages(float $percentage, int $expected): void
    {
        // act & assert
        $this->assertSame($expected, TaxCalculator::toBasisPoints($percentage));
    }

    /**
     * @return array<string, array{float, int}>
     */
    public static function basisPoints(): array
    {
        return [
            'whole' => [21.0, 2100],
            'fraction' => [9.5, 950],
            'float noise' => [0.21 * 100, 2100],
            'zero' => [0.0, 0],
        ];
    }

    private function item(float $unitPrice, float|int|null $taxPercentage): InvoiceItem
    {
        return InvoiceItem::make(['title' => 'Item', 'unit_price' => $unitPrice, 'tax_percentage' => $taxPercentage]);
    }
}
