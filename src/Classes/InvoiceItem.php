<?php

namespace SimpleParkBv\Invoices;

use SimpleParkBv\Invoices\Contracts\InvoiceItemInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException;

/**
 * Class InvoiceItem
 *
 * @property string $title
 * @property string|null $description
 * @property float|int $quantity
 * @property float|int $unit_price
 * @property float|null $tax_percentage
 */
final class InvoiceItem implements InvoiceItemInterface
{
    public string $title;

    public ?string $description;

    public float|int $quantity;

    public float|int $unit_price;

    public ?float $tax_percentage;

    public static function make(): self
    {
        return new self;
    }

    /**
     * Set the title of the item.
     *
     * @return $this
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the description of the item.
     *
     * @return $this
     */
    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the quantity of the item.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException
     */
    public function quantity(float|int $quantity): self
    {
        if ($quantity <= 0) {
            throw new InvalidInvoiceItemException('Quantity must be greater than 0');
        }

        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Set the unit price of the item.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException
     */
    public function unitPrice(float|int $unitPrice): self
    {
        if ($unitPrice < 0) {
            throw new InvalidInvoiceItemException('Unit price must be greater than or equal to 0');
        }

        $this->unit_price = $unitPrice;

        return $this;
    }

    /**
     * Set the tax percentage of the item.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException
     */
    public function taxPercentage(?float $taxPercentage): self
    {
        if ($taxPercentage !== null && ($taxPercentage < 0 || $taxPercentage > 100)) {
            throw new InvalidInvoiceItemException('Tax percentage must be between 0 and 100, or null');
        }

        $this->tax_percentage = $taxPercentage;

        return $this;
    }

    /**
     * Calculate the total for this item (quantity * unit_price).
     */
    public function total(): float
    {
        return $this->unit_price * $this->quantity;
    }

    /**
     * Get the formatted tax percentage for display.
     * Returns empty string if tax percentage is null, otherwise returns the percentage with % sign.
     */
    public function formattedTaxPercentage(): string
    {
        if ($this->tax_percentage === null) {
            return '';
        }

        return $this->tax_percentage.'%';
    }

    /**
     * Validate the invoice item.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException
     */
    public function validate(?int $index = null): void
    {
        $prefix = $index !== null ? "Item at index {$index}" : 'Item';

        if (empty($this->title)) {
            throw new InvalidInvoiceItemException("{$prefix} must have a title");
        }

        if ($this->quantity <= 0) {
            throw new InvalidInvoiceItemException("{$prefix} must have a quantity greater than 0");
        }

        if ($this->unit_price < 0) {
            throw new InvalidInvoiceItemException("{$prefix} must have a unit_price greater than or equal to 0");
        }

        if ($this->tax_percentage !== null && ($this->tax_percentage < 0 || $this->tax_percentage > 100)) {
            throw new InvalidInvoiceItemException("{$prefix} must have a tax_percentage between 0 and 100, or null");
        }
    }
}
