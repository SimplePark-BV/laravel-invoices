<?php

namespace SimpleParkBv\Invoices\Services;

use Illuminate\Support\Collection;
use SimpleParkBv\Invoices\InvoiceItem;

/**
 * Service class for calculating taxes on invoice items.
 */
final class TaxCalculator
{
    /**
     * Calculate the total tax amount for a collection of items.
     *
     * Sums taxes only for items that have a tax_percentage set (excludes null items).
     * Calculates tax from unit_price which includes tax.
     *
     * @param  \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\InvoiceItem>  $items
     */
    public static function calculateTaxAmount(Collection $items): float
    {
        return $items->sum(function (InvoiceItem $item): float {
            if ($item->tax_percentage === null || $item->tax_percentage <= 0) {
                return 0;
            }

            $taxRate = $item->tax_percentage / 100;
            $itemTotal = $item->unit_price * $item->quantity;

            // tax amount = price including tax * taxRate / (1 + taxRate)
            return $itemTotal * $taxRate / (1 + $taxRate);
        });
    }

    /**
     * Get all unique tax percentages from items, excluding null values.
     * Returns a collection sorted in descending order.
     *
     * @param  \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\InvoiceItem>  $items
     * @return \Illuminate\Support\Collection<int, float>
     */
    public static function extractTaxGroups(Collection $items): Collection
    {
        return $items
            ->pluck('tax_percentage')
            ->filter(static fn (?float $taxPercentage): bool => $taxPercentage !== null && $taxPercentage > 0)
            ->unique()
            ->sortByDesc(static fn (float $taxPercentage): float => $taxPercentage)
            ->values();
    }

    /**
     * Calculate the tax amount for items with a specific tax percentage.
     * Calculates tax from unit_price which includes tax.
     *
     * @param  \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\InvoiceItem>  $items
     */
    public static function calculateTaxForGroup(Collection $items, float $taxPercentage): float
    {
        $taxRate = $taxPercentage / 100;

        return $items
            ->filter(fn (InvoiceItem $item) => $item->tax_percentage === $taxPercentage)
            ->sum(function (InvoiceItem $item) use ($taxRate): float {
                $itemTotal = $item->unit_price * $item->quantity;

                // tax amount = price including tax * taxRate / (1 + taxRate)
                return $itemTotal * $taxRate / (1 + $taxRate);
            });
    }

    /**
     * Calculate the subtotal for items with a specific tax percentage.
     * Calculated as: sum of items in this tax group - tax amount for this group.
     *
     * @param  \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\InvoiceItem>  $items
     */
    public static function calculateSubTotalForTaxGroup(Collection $items, float $taxPercentage): float
    {
        $itemsTotal = $items
            ->filter(fn (InvoiceItem $item) => $item->tax_percentage === $taxPercentage)
            ->sum(fn (InvoiceItem $item): float => $item->unit_price * $item->quantity);

        return $itemsTotal - self::calculateTaxForGroup($items, $taxPercentage);
    }
}
