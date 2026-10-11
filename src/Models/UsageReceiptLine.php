<?php

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Contracts\TaxableInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Services\CurrencyFormatter;

/**
 * Class UsageReceiptLine
 *
 * One line of the cost breakdown under the sessions of a usage receipt, such as a fee or a discount.
 * A discount is a negative amount. The amount includes tax, like the unit price of an invoice item.
 *
 * @property string $label
 * @property float $amount
 * @property float|null $taxPercentage
 */
final class UsageReceiptLine implements TaxableInterface
{
    use CanFillFromArray;

    private ?string $label = null;

    private ?float $amount = null;

    private ?float $taxPercentage = null;

    /**
     * Set the label.
     *
     * @return $this
     */
    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the amount, negative for a discount.
     *
     * @return $this
     */
    public function amount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set the tax percentage, or null when no tax applies.
     *
     * @return $this
     */
    public function taxPercentage(?float $taxPercentage): self
    {
        $this->taxPercentage = $taxPercentage;

        return $this;
    }

    /**
     * Get the label.
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Get the amount.
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * Get the tax percentage.
     */
    public function getTaxPercentage(): ?float
    {
        return $this->taxPercentage;
    }

    /**
     * Get the total including tax, used for the tax calculation.
     */
    public function getTotal(): float
    {
        return $this->amount ?? 0.0;
    }

    /**
     * Get the formatted tax percentage for display, empty when no tax applies.
     */
    public function getFormattedTaxPercentage(): string
    {
        if ($this->taxPercentage === null) {
            return '';
        }

        return $this->taxPercentage.'%';
    }

    /**
     * Get the formatted amount with currency symbol.
     */
    public function getFormattedAmount(): string
    {
        return $this->amount !== null ? CurrencyFormatter::format($this->amount) : '';
    }

    /**
     * Validate the line.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException
     */
    public function validate(?int $index = null): void
    {
        $prefix = $index !== null ? "Line at index {$index}" : 'Line';

        if ($this->label === null || $this->label === '') {
            throw new InvalidUsageReceiptException("{$prefix} must have a label");
        }

        if ($this->amount === null) {
            throw new InvalidUsageReceiptException("{$prefix} must have an amount");
        }

        if ($this->taxPercentage !== null && ($this->taxPercentage < 0 || $this->taxPercentage > 100)) {
            throw new InvalidUsageReceiptException("{$prefix} must have a taxPercentage between 0 and 100, or null");
        }
    }

    /**
     * Convert the line to an array representation.
     *
     * @return array{label: string|null, amount: float|null, tax_percentage: float|null}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'amount' => $this->amount,
            'tax_percentage' => $this->taxPercentage,
        ];
    }
}
