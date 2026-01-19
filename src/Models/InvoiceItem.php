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

    public string $title;

    public ?string $description;

    public float|int $quantity;

    public float|int $unitPrice;

    public ?float $taxPercentage;

    public static function make(): self
    {
        return new self;
    }

    /**
     * Create an invoice item from an array of data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::make()->fill($data);
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

        $this->taxPercentage = $taxPercentage;

        return $this;
    }

    /**
     * Get the title of the item.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

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
     * Get the unit price of the item.
     */
    public function getUnitPrice(): float|int
    {
        return $this->unitPrice;
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
