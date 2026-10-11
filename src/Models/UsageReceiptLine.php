<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Contracts\TaxableInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Support\Money;
use SimpleParkBv\Invoices\Support\Text;

/**
 * A line of the cost breakdown under the sessions of a usage receipt, such as a fee or a discount.
 *
 * The amount includes tax; a discount is a negative amount. When a receipt has lines, they make up its total.
 */
final class UsageReceiptLine implements TaxableInterface
{
    use CanFillFromArray;

    private ?string $label = null;

    private ?int $amountCents = null;

    private ?float $taxPercentage = null;

    /**
     * Set the label.
     *
     * @return $this
     */
    public function label(?string $label): static
    {
        $this->label = Text::clean($label);

        return $this;
    }

    /**
     * Set the amount including tax, with at most 2 decimals, negative for a discount.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException when the amount is not a number with at most 2 decimals
     */
    public function amount(float|int|string|null $amount): static
    {
        $this->amountCents = Money::toCents($amount, 'amount', InvalidUsageReceiptException::class);

        return $this;
    }

    /**
     * Set the tax percentage the amount includes, e.g. 21, or null when no tax applies.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException when the percentage is not a number
     */
    public function taxPercentage(float|int|string|null $taxPercentage): static
    {
        $this->taxPercentage = $taxPercentage === null
            ? null
            : Money::toNumber($taxPercentage, 'tax percentage', InvalidUsageReceiptException::class);

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getAmount(): ?float
    {
        return $this->amountCents === null ? null : Money::toFloat($this->amountCents);
    }

    public function getAmountCents(): ?int
    {
        return $this->amountCents;
    }

    public function getTaxPercentage(): ?float
    {
        return $this->taxPercentage;
    }

    /**
     * Get the amount including tax, in cents.
     */
    public function getTotalCents(): int
    {
        return $this->amountCents ?? 0;
    }

    /**
     * Validate the line.
     *
     * @param  int|null  $index  the position of the line on the receipt, used in the message
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException
     */
    public function validate(?int $index = null): void
    {
        $prefix = $index === null ? 'Line' : "Line at index {$index}";

        if ($this->label === null) {
            throw new InvalidUsageReceiptException("{$prefix} must have a label");
        }

        if ($this->amountCents === null) {
            throw new InvalidUsageReceiptException("{$prefix} must have an amount");
        }

        if ($this->taxPercentage !== null && ($this->taxPercentage < 0 || $this->taxPercentage > 100)) {
            throw new InvalidUsageReceiptException("{$prefix} must have a tax percentage between 0 and 100, or null");
        }
    }

    /**
     * Convert the line to an array that make() accepts.
     *
     * @return array{label: string|null, amount: float|null, tax_percentage: float|null}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'amount' => $this->getAmount(),
            'tax_percentage' => $this->taxPercentage,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function fillable(): array
    {
        return [
            'label' => 'label',
            'amount' => 'amount',
            'tax_percentage' => 'taxPercentage',
        ];
    }

    /**
     * @return class-string<\SimpleParkBv\Invoices\Exceptions\InvalidDocumentException>
     */
    protected static function invalidDataException(): string
    {
        return InvalidUsageReceiptException::class;
    }
}
