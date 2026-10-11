<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Support\Money;

/**
 * The totals of a document, computed once in whole cents so the printed breakdown always adds up:
 * the line totals add up to the total, and the subtotal plus the tax of every group is the total.
 */
final readonly class Totals
{
    /**
     * @param  list<\SimpleParkBv\Invoices\Models\TaxGroup>  $taxGroups  the tax groups, highest percentage first
     */
    public function __construct(
        private int $totalCents,
        private array $taxGroups,
    ) {
        //
    }

    /**
     * Get the total including tax, in cents.
     */
    public function getTotalCents(): int
    {
        return $this->totalCents;
    }

    /**
     * Get the total including tax.
     */
    public function getTotal(): float
    {
        return Money::toFloat($this->totalCents);
    }

    /**
     * Get the total tax, the sum of the tax of every group, in cents.
     */
    public function getTaxCents(): int
    {
        $cents = 0;

        foreach ($this->taxGroups as $group) {
            $cents += $group->getTaxCents();
        }

        return $cents;
    }

    /**
     * Get the total tax, the sum of the tax of every group.
     */
    public function getTax(): float
    {
        return Money::toFloat($this->getTaxCents());
    }

    /**
     * Get the total excluding tax, in cents.
     */
    public function getSubtotalCents(): int
    {
        return $this->totalCents - $this->getTaxCents();
    }

    /**
     * Get the total excluding tax.
     */
    public function getSubtotal(): float
    {
        return Money::toFloat($this->getSubtotalCents());
    }

    /**
     * Get the tax groups, highest percentage first. Lines without a tax percentage are in no group.
     *
     * @return list<\SimpleParkBv\Invoices\Models\TaxGroup>
     */
    public function getTaxGroups(): array
    {
        return $this->taxGroups;
    }
}
