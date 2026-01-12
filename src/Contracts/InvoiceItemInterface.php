<?php

namespace SimpleParkBv\Invoices\Contracts;

/**
 * Interface for invoice item implementations.
 */
interface InvoiceItemInterface
{
    /**
     * Calculate the total for this item (quantity * unit_price).
     */
    public function getTotal(): float;

    /**
     * Get the formatted tax percentage for display.
     */
    public function getFormattedTaxPercentage(): string;
}
