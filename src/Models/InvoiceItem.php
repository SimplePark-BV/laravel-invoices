<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Contracts\TaxableInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Support\Money;
use SimpleParkBv\Invoices\Support\Text;

/**
 * A line of an invoice. The unit price includes tax; a negative unit price is a discount.
 */
final class InvoiceItem implements TaxableInterface
{
    use CanFillFromArray;

    private ?string $title = null;

    private ?string $description = null;

    private float|int $quantity = 1;

    private ?int $unitPriceCents = null;

    private ?float $taxPercentage = null;

    /**
     * Set the title.
     *
     * @return $this
     */
    public function title(?string $title): static
    {
        $this->title = Text::clean($title);

        return $this;
    }

    /**
     * Set the description, shown below the title.
     *
     * @return $this
     */
    public function description(?string $description): static
    {
        $this->description = Text::clean($description);

        return $this;
    }

    /**
     * Set the quantity, 1 by default. Fractional quantities such as 1.5 are allowed.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException when the quantity is not a number
     */
    public function quantity(float|int|string $quantity): static
    {
        $quantity = Money::toNumber($quantity, 'quantity', InvalidInvoiceException::class);

        $this->quantity = floor($quantity) === $quantity && abs($quantity) < 1e15 ? (int) $quantity : $quantity;

        return $this;
    }

    /**
     * Set the price of one unit, including tax, with at most 2 decimals.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException when the price is not a number with at most 2 decimals
     */
    public function unitPrice(float|int|string|null $unitPrice): static
    {
        $this->unitPriceCents = Money::toCents($unitPrice, 'unit price', InvalidInvoiceException::class);

        return $this;
    }

    /**
     * Set the tax percentage the unit price includes, e.g. 21, or null when no tax applies.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException when the percentage is not a number
     */
    public function taxPercentage(float|int|string|null $taxPercentage): static
    {
        $this->taxPercentage = $taxPercentage === null
            ? null
            : Money::toNumber($taxPercentage, 'tax percentage', InvalidInvoiceException::class);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getQuantity(): float|int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPriceCents === null ? null : Money::toFloat($this->unitPriceCents);
    }

    public function getUnitPriceCents(): ?int
    {
        return $this->unitPriceCents;
    }

    public function getTaxPercentage(): ?float
    {
        return $this->taxPercentage;
    }

    /**
     * Get the line total (quantity times unit price) including tax, rounded to whole cents.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException when the line total is too large
     */
    public function getTotalCents(): int
    {
        return Money::multiply($this->unitPriceCents ?? 0, $this->quantity, 'line total', InvalidInvoiceException::class);
    }

    /**
     * Get the line total (quantity times unit price) including tax, rounded to whole cents.
     */
    public function getTotal(): float
    {
        return Money::toFloat($this->getTotalCents());
    }

    /**
     * Validate the item.
     *
     * @param  int|null  $index  the position of the item on the invoice, used in the message
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     */
    public function validate(?int $index = null): void
    {
        $prefix = $index === null ? 'Item' : "Item at index {$index}";

        if ($this->title === null) {
            throw new InvalidInvoiceException("{$prefix} must have a title");
        }

        if ($this->quantity <= 0) {
            throw new InvalidInvoiceException("{$prefix} must have a quantity greater than 0");
        }

        if ($this->unitPriceCents === null) {
            throw new InvalidInvoiceException("{$prefix} must have a unit price");
        }

        if ($this->taxPercentage !== null && ($this->taxPercentage < 0 || $this->taxPercentage > 100)) {
            throw new InvalidInvoiceException("{$prefix} must have a tax percentage between 0 and 100, or null");
        }
    }

    /**
     * Convert the item to an array that make() accepts.
     *
     * @return array{title: string|null, description: string|null, quantity: float|int, unit_price: float|null, tax_percentage: float|null}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->getUnitPrice(),
            'tax_percentage' => $this->taxPercentage,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function fillable(): array
    {
        return [
            'title' => 'title',
            'description' => 'description',
            'quantity' => 'quantity',
            'unit_price' => 'unitPrice',
            'tax_percentage' => 'taxPercentage',
        ];
    }

    /**
     * @return class-string<\SimpleParkBv\Invoices\Exceptions\InvalidDocumentException>
     */
    protected static function invalidDataException(): string
    {
        return InvalidInvoiceException::class;
    }
}
