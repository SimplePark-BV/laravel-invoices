<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Support\Money;

/**
 * The amounts of one tax rate on a document.
 */
final readonly class TaxGroup
{
    public function __construct(
        private float $percentage,
        private int $grossCents,
        private int $taxCents,
    ) {
        //
    }

    /**
     * Get the tax percentage of the group, e.g. 21.0.
     */
    public function getPercentage(): float
    {
        return $this->percentage;
    }

    /**
     * Get the amount including tax, in cents.
     */
    public function getGrossCents(): int
    {
        return $this->grossCents;
    }

    /**
     * Get the tax included in the amount, in cents.
     */
    public function getTaxCents(): int
    {
        return $this->taxCents;
    }

    /**
     * Get the amount excluding tax (the taxable base), in cents.
     */
    public function getNetCents(): int
    {
        return $this->grossCents - $this->taxCents;
    }

    /**
     * Get the amount including tax.
     */
    public function getGross(): float
    {
        return Money::toFloat($this->grossCents);
    }

    /**
     * Get the tax included in the amount.
     */
    public function getTax(): float
    {
        return Money::toFloat($this->taxCents);
    }

    /**
     * Get the amount excluding tax (the taxable base).
     */
    public function getNet(): float
    {
        return Money::toFloat($this->getNetCents());
    }
}
