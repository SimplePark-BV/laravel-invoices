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

    public ?string $description;

    public float|int $quantity;

    public float|int $price;

    public ?float $tax_rate;

    public function __construct()
    {
        // todo
    }

    public static function make(): self
    {
        return new self;
    }
}
