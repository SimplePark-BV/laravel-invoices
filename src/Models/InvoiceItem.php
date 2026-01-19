<?php

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Contracts\InvoiceItemInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;

/**
 * Class InvoiceItem
 *
 * @property string $title
 * @property string|null $description
 * @property float|int $quantity
 * @property float|int $unitPrice
 * @property float|null $taxPercentage
 */
final class InvoiceItem implements InvoiceItemInterface
{
    use CanFillFromArray;

    private string $title = '';

    private ?string $description = null;

    private float|int $quantity = 0;

    private float|int $unitPrice = 0;

    private ?float $taxPercentage = null;

    /**
     * Get the description of the item.
     */
    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    /**
     * Get the quantity of the item.
     */
    public function getQuantity(): float|int
    {
        return $this->quantity;
    }

    /**
     * Get the tax percentage of the item.
     */
    public function getTaxPercentage(): ?float
    {
        return $this->taxPercentage;
    }

    /**
     * Get the title of the item.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the unit price of the item.
     */
    public function getUnitPrice(): float|int
    {
        return $this->unitPrice;
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
     */
    public function quantity(float|int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Set the tax percentage of the item.
     *
     * @return $this
     */
    public function taxPercentage(?float $taxPercentage): self
    {
        $this->taxPercentage = $taxPercentage;

        return $this;
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
     * Set the unit price of the item.
     * Negative values are allowed for discount items.
     *
     * @return $this
     */
    public function unitPrice(float|int $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * Calculate the total for this item (quantity * unitPrice).
     */
    public function getTotal(): float
    {
        return $this->unitPrice * $this->quantity;
    }

    /**
     * Get the formatted tax percentage for display.
     * Returns empty string if tax percentage is null, otherwise returns the percentage with % sign.
     */
    public function getFormattedTaxPercentage(): string
    {
        if ($this->taxPercentage === null) {
            return '';
        }

        return $this->taxPercentage.'%';
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

        // unit price can be negative for discount items

        if (isset($this->taxPercentage) && ($this->taxPercentage < 0 || $this->taxPercentage > 100)) {
            throw new InvalidInvoiceItemException("{$prefix} must have a taxPercentage between 0 and 100, or null");
        }
    }

    /**
     * Convert the invoice item to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description ?? null,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'tax_percentage' => $this->taxPercentage ?? null,
        ];
    }
}
