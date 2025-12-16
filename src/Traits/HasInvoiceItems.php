<?php

namespace SimpleParkBv\Invoices\Traits;

use Illuminate\Support\Collection;
use SimpleParkBv\Invoices\InvoiceItem;
use SimpleParkBv\Invoices\Services\CurrencyFormatter;
use SimpleParkBv\Invoices\Services\TaxCalculator;

/**
 * Trait HasInvoiceItems
 */
trait HasInvoiceItems
{
    /**
     * @var \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\InvoiceItem>
     */
    public Collection $items;

    public ?float $forcedTotal = null;

    public function initializeHasInvoiceItems(): void
    {
        $this->items = collect();
        $this->forcedTotal = null;
    }

    /**
     * Add a single item to the invoice.
     */
    public function addItem(InvoiceItem $item): self
    {
        $this->items->push($item);

        return $this;
    }

    /**
     * Add multiple items to the invoice.
     *
     * @param  array<int, \SimpleParkBv\Invoices\InvoiceItem>  $items
     */
    public function addItems(array $items): self
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    /**
     * Calculate the total tax amount.
     *
     * Sums taxes only for items that have a tax_percentage set (excludes null items).
     * Calculates tax from unit_price which includes tax.
     */
    public function taxAmount(): float
    {
        return TaxCalculator::calculateTaxAmount($this->items);
    }

    /**
     * Get the sum of all items (unit_price * quantity for all items).
     * This is the total before subtracting VATs.
     */
    public function itemsTotal(): float
    {
        return $this->items->sum(
            static fn (InvoiceItem $item): float => $item->unit_price * $item->quantity
        );
    }

    /**
     * Calculate the subtotal (total excluding tax).
     *
     * Calculated as: sum of all items - all vats.
     * This ensures: sum of all items = subtotal + vats.
     */
    public function subTotal(): float
    {
        return $this->itemsTotal() - $this->taxAmount();
    }

    /**
     * Get the formatted subtotal with proper rounding to avoid rounding discrepancies.
     *
     * Calculated as: total - sum of all rounded tax groups (each rounded to 2 decimals).
     * This ensures: total = formattedSubTotal + sum of all tax groups (with proper rounding).
     */
    public function formattedSubTotal(): float
    {
        $total = $this->total();
        $sumOfRoundedTaxGroups = $this->taxGroups()
            ->sum(fn (float $taxPercentage): float => round($this->taxAmountForTaxGroup($taxPercentage), 2));

        return round($total - $sumOfRoundedTaxGroups, 2);
    }

    /**
     * Force a specific total amount that will override the calculated total.
     * Useful when you need to ensure the total matches a specific amount (e.g., from external systems).
     */
    public function forcedTotal(float $amount): self
    {
        $this->forcedTotal = $amount;

        return $this;
    }

    /**
     * Calculate the grand total.
     *
     * Returns the forced total if set via forcedTotal(), otherwise returns the sum of all items (itemsTotal).
     * This ensures accuracy to the cent and allows overriding when needed.
     *
     * WARNING: Do not use this method for calculations (e.g., subtotal + tax = total).
     * When forcedTotal() is set, the returned amount may differ from the calculated sum of items.
     * Use itemsTotal() for calculations that need to match the actual sum of all items.
     */
    public function total(): float
    {
        if ($this->forcedTotal !== null) {
            return $this->forcedTotal;
        }

        // always calculate from items directly to ensure precision to the cent
        return $this->itemsTotal();
    }

    /**
     * Get the formatted total amount with currency symbol.
     */
    public function formattedTotal(): string
    {
        return CurrencyFormatter::format($this->total());
    }

    /**
     * Get all unique tax percentages from items, excluding null values.
     * Returns a collection sorted in descending order.
     *
     * @return \Illuminate\Support\Collection<int, float>
     */
    public function taxGroups(): Collection
    {
        return TaxCalculator::extractTaxGroups($this->items);
    }

    /**
     * Calculate the subtotal for items with a specific tax percentage.
     * Calculated as: sum of items in this tax group - tax amount for this group.
     */
    public function subTotalForTaxGroup(float $taxPercentage): float
    {
        return TaxCalculator::calculateSubTotalForTaxGroup($this->items, $taxPercentage);
    }

    /**
     * Calculate the tax amount for items with a specific tax percentage.
     * Calculates tax from unit_price which includes tax.
     */
    public function taxAmountForTaxGroup(float $taxPercentage): float
    {
        return TaxCalculator::calculateTaxForGroup($this->items, $taxPercentage);
    }
}
