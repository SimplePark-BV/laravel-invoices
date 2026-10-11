<?php

namespace SimpleParkBv\Invoices\Contracts;

/**
 * Interface for anything taxes can be calculated on, such as invoice items and usage receipt lines.
 */
interface TaxableInterface
{
    /**
     * Get the tax percentage, or null when no tax applies.
     */
    public function getTaxPercentage(): ?float;

    /**
     * Get the total including tax.
     */
    public function getTotal(): float;
}
