<?php

namespace SimpleParkBv\Invoices\Services;

use Illuminate\Support\Collection;
use SimpleParkBv\Invoices\Models\InvoiceItem;

/**
 * Service class for calculating taxes on invoice items.
 *
 * All monetary values and tax percentages are rounded to a fixed precision
 * to prevent rounding drift and ensure consistent results across calculations.
 * Precision values are configurable via the invoices config file.
 */
final class TaxCalculator
{
    /**
     * Get the decimal precision for monetary values (e.g., currency amounts).
     */
    private static function getPrecision(): int
    {
        return (int) config('invoices.precision.monetary', 2);
    }

    /**
     * Get the decimal precision for tax percentages.
     */
    private static function getTaxPercentagePrecision(): int
    {
        return (int) config('invoices.precision.tax_percentage', 2);
    }

    /**
     * Get the epsilon value for tax percentage comparison.
     */
    private static function getTaxPercentageEpsilon(): float
    {
        return (float) config('invoices.precision.tax_percentage_epsilon', 0.005);
    }

    /**
     * Calculate the total tax amount for a collection of items.
     *
     * Sums taxes only for items that have a tax_percentage set (excludes null items).
     * Calculates tax from unit_price which includes tax.
     *
     * Returns the total tax amount rounded to the configured precision to prevent rounding drift.
     *
     * @param  \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Models\InvoiceItem>  $items
     * @return float The total tax amount, rounded to the configured precision
     */
    public static function calculateTaxAmount(Collection $items): float
    {
        $total = $items->sum(function (InvoiceItem $item): float {
            if ($item->tax_percentage === null || $item->tax_percentage <= 0) {
                return 0;
            }

            $taxRate = $item->tax_percentage / 100;
            $itemTotal = $item->unit_price * $item->quantity;

            // tax amount = price including tax * taxRate / (1 + taxRate)
            return $itemTotal * $taxRate / (1 + $taxRate);
        });

        return round($total, self::getPrecision());
    }

    /**
     * Get all unique tax percentages from items, excluding null values.
     *
     * Returns a collection sorted in descending order. All tax percentages are
     * rounded to the configured precision to ensure consistent grouping of
     * equivalent percentages that may have different floating-point representations.
     *
     * @param  \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Models\InvoiceItem>  $items
     * @return \Illuminate\Support\Collection<int, float> Tax percentages rounded to the configured precision
     */
    public static function extractTaxGroups(Collection $items): Collection
    {
        return $items
            ->pluck('tax_percentage')
            ->filter(static fn (?float $taxPercentage): bool => $taxPercentage !== null && $taxPercentage > 0)
            ->map(static fn (float $taxPercentage): float => round($taxPercentage, self::getTaxPercentagePrecision()))
            ->unique()
            ->sortByDesc(static fn (float $taxPercentage): float => $taxPercentage)
            ->values();
    }

    /**
     * Calculate the tax amount for items with a specific tax percentage.
     *
     * Calculates tax from unit_price which includes tax. Items are matched by
     * comparing tax percentages using epsilon comparison to handle floating-point
     * precision differences. Returns the total rounded to the configured precision.
     *
     * @param  \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Models\InvoiceItem>  $items
     * @param  float  $taxPercentage  The tax percentage to filter by (will be normalized)
     * @return float The tax amount for the group, rounded to the configured precision
     */
    public static function calculateTaxForGroup(Collection $items, float $taxPercentage): float
    {
        $taxRate = $taxPercentage / 100;
        $taxPercentagePrecision = self::getTaxPercentagePrecision();
        $normalizedTaxPercentage = round($taxPercentage, $taxPercentagePrecision);
        $epsilon = self::getTaxPercentageEpsilon();

        $total = $items
            ->filter(fn (InvoiceItem $item) => $item->tax_percentage !== null && abs(round($item->tax_percentage, $taxPercentagePrecision) - $normalizedTaxPercentage) < $epsilon)
            ->sum(function (InvoiceItem $item) use ($taxRate): float {
                $itemTotal = $item->unit_price * $item->quantity;

                // tax amount = price including tax * taxRate / (1 + taxRate)
                return $itemTotal * $taxRate / (1 + $taxRate);
            });

        return round($total, self::getPrecision());
    }

    /**
     * Calculate the subtotal for items with a specific tax percentage.
     *
     * Calculated as: sum of items in this tax group - tax amount for this group.
     * Items are matched by comparing tax percentages using epsilon comparison.
     * Returns the subtotal rounded to the configured precision.
     *
     * @param  \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Models\InvoiceItem>  $items
     * @param  float  $taxPercentage  The tax percentage to filter by (will be normalized)
     * @return float The subtotal for the group, rounded to the configured precision
     */
    public static function calculateSubTotalForTaxGroup(Collection $items, float $taxPercentage): float
    {
        $taxPercentagePrecision = self::getTaxPercentagePrecision();
        $normalizedTaxPercentage = round($taxPercentage, $taxPercentagePrecision);
        $epsilon = self::getTaxPercentageEpsilon();
        $itemsTotal = $items
            ->filter(fn (InvoiceItem $item) => $item->tax_percentage !== null && abs(round($item->tax_percentage, $taxPercentagePrecision) - $normalizedTaxPercentage) < $epsilon)
            ->sum(fn (InvoiceItem $item): float => $item->unit_price * $item->quantity);

        $subtotal = $itemsTotal - self::calculateTaxForGroup($items, $taxPercentage);

        return round($subtotal, self::getPrecision());
    }
}
