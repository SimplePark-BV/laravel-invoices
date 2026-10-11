<?php

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Services\CurrencyFormatter;

/**
 * Class UsageReceiptLine
 *
 * One line of the cost breakdown under the sessions of a usage receipt, such as a fee or a discount.
 * A discount is a negative amount.
 *
 * @property string $label
 * @property float $amount
 */
final class UsageReceiptLine
{
    use CanFillFromArray;

    private ?string $label = null;

    private ?float $amount = null;

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
    }

    /**
     * Convert the line to an array representation.
     *
     * @return array{label: string|null, amount: float|null}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'amount' => $this->amount,
        ];
    }
}
