<?php

namespace SimpleParkBv\Invoices;

/**
 * Class InvoiceItem
 *
 * @property string $title
 * @property string|null $description
 * @property float|int $quantity
 * @property float|int $price
 * @property float|null $tax_rate
 */
final class InvoiceItem
{
    public string $title;

    public string|null $description;

    public float|int $quantity;

    public float|null $tax_rate;

    public float|int $unit_price;

    public float|null $tax_percentage;

    public static function make(): self
    {
        return new self;
    }

    /**
     * Calculate the total for this item (quantity * unit_price).
     */
    public function total(): float
    {
        return $this->unit_price * $this->quantity;
    }
}
