<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Contracts;

/**
 * Anything that adds an amount including tax to a document, such as invoice items and usage receipt lines.
 */
interface TaxableInterface
{
    /**
     * Get the tax percentage, or null when no tax applies.
     */
    public function getTaxPercentage(): ?float;

    /**
     * Get the total including tax, in cents.
     */
    public function getTotalCents(): int;
}
