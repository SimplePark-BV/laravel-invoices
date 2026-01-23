<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use Illuminate\Support\Collection;
use SimpleParkBv\Invoices\Contracts\InvoiceItemInterface;
use SimpleParkBv\Invoices\Services\CurrencyFormatter;
use SimpleParkBv\Invoices\Services\TaxCalculator;

/**
 * Trait HasInvoiceItems
 */
trait HasInvoiceItems
{
    use HasExpectedTotal;

    /**
     * @var \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Contracts\InvoiceItemInterface>
     */
    protected Collection $items;

    public function initializeHasInvoiceItems(): void
    {
        $this->items = collect();
    }

    /**
     * Get all items.
     *
     * @return \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Contracts\InvoiceItemInterface>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Add a single item to the invoice.
     */
    public function addItem(InvoiceItemInterface $item): self
    {
        $this->items->push($item);

        return $this;
    }

    /**
     * Set all items for the invoice (replaces existing items).
     *
     * @param  array<int, \SimpleParkBv\Invoices\Contracts\InvoiceItemInterface>  $items
     */
    public function items(array $items): self
    {
        $this->items = collect($items);

        return $this;
    }

    /**
     * Calculate the total tax amount.
     *
     * Sums taxes only for items that have a taxPercentage set (excludes null items).
     * Calculates tax from unitPrice which includes tax.
     */
    public function getTaxAmount(): float
    {
        return TaxCalculator::calculateTaxAmount($this->items);
    }

    /**
     * Get the sum of all items (unitPrice * quantity for all items).
     * This is the total before subtracting VATs.
     */
    public function getItemsTotal(): float
    {
        return $this->getItems()->sum(
            static fn (InvoiceItemInterface $item): float => $item->getUnitPrice() * $item->getQuantity()
        );
    }

    /**
     * Calculate the subtotal (total excluding tax).
     *
     * Calculated as: sum of all items - all vats.
     * This ensures: sum of all items = subtotal + vats.
     */
    public function getSubTotal(): float
    {
        return $this->getItemsTotal() - $this->getTaxAmount();
    }

    /**
     * Get the formatted subtotal with proper rounding to avoid rounding discrepancies.
     *
     * Calculated as: total - sum of all rounded tax groups (each rounded to 2 decimals).
     * This ensures: total = formattedSubTotal + sum of all tax groups (with proper rounding).
     */
    public function getFormattedSubTotal(): float
    {
        $total = $this->getTotal();
        $sumOfRoundedTaxGroups = $this->getTaxGroups()
            ->sum(fn (float $taxPercentage): float => round($this->getTaxAmountForTaxGroup($taxPercentage), 2));

        return round($total - $sumOfRoundedTaxGroups, 2);
    }

    /**
     * Calculate the grand total.
     *
     * Returns the sum of all items (getItemsTotal).
     * This ensures accuracy to the cent.
     */
    public function getTotal(): float
    {
        // always calculate from items directly to ensure precision to the cent
        return $this->getItemsTotal();
    }

    /**
     * Get the formatted total amount with currency symbol.
     */
    public function getFormattedTotal(): string
    {
        return CurrencyFormatter::format($this->getTotal());
    }

    /**
     * Get all unique tax percentages from items, excluding null values.
     * Returns a collection sorted in descending order.
     *
     * @return \Illuminate\Support\Collection<int, float>
     */
    public function getTaxGroups(): Collection
    {
        return TaxCalculator::extractTaxGroups($this->items);
    }

    /**
     * Calculate the subtotal for items with a specific tax percentage.
     *
     * Calculated as: sum of items in this tax group - tax amount for this group.
     */
    public function getSubTotalForTaxGroup(float $taxPercentage): float
    {
        return TaxCalculator::calculateSubTotalForTaxGroup($this->items, $taxPercentage);
    }

    /**
     * Calculate the tax amount for items with a specific tax percentage.
     *
     * Calculates tax from unitPrice which includes tax.
     */
    public function getTaxAmountForTaxGroup(float $taxPercentage): float
    {
        return TaxCalculator::calculateTaxForGroup($this->items, $taxPercentage);
    }
}
