<?php

namespace SimpleParkBv\Invoices\Contracts;

/**
 * Interface for invoice item implementations.
 */
interface InvoiceItemInterface
{
    /**
     * Create a new invoice item instance.
     *
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data = []): self;

    /**
     * Set the title of the item.
     */
    public function title(string $title): self;

    /**
     * Set the description of the item.
     */
    public function description(?string $description): self;

    /**
     * Set the quantity of the item.
     */
    public function quantity(float|int $quantity): self;

    /**
     * Set the unit price of the item.
     */
    public function unitPrice(float|int $unitPrice): self;

    /**
     * Set the tax percentage of the item.
     */
    public function taxPercentage(?float $taxPercentage): self;

    /**
     * Set the tax rate of the item (decimal, e.g. 0.21 for 21%).
     */
    public function taxRate(float $taxRate): self;

    /**
     * Get the title of the item.
     */
    public function getTitle(): string;

    /**
     * Get the description of the item.
     */
    public function getDescription(): ?string;

    /**
     * Get the quantity of the item.
     */
    public function getQuantity(): float|int;

    /**
     * Get the unit price of the item.
     */
    public function getUnitPrice(): float|int;

    /**
     * Get the tax percentage of the item.
     */
    public function getTaxPercentage(): ?float;

    /**
     * Calculate the total for this item (quantity * unit_price).
     */
    public function getTotal(): float;

    /**
     * Get the formatted tax percentage for display.
     */
    public function getFormattedTaxPercentage(): string;

    /**
     * Validate the invoice item.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException
     */
    public function validate(?int $index = null): void;

    /**
     * Convert the invoice item to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
