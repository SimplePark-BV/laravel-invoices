<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use SimpleParkBv\Invoices\Contracts\TaxableInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Support\Dates;
use SimpleParkBv\Invoices\Support\Money;
use SimpleParkBv\Invoices\Support\Text;

/**
 * A session on a usage receipt, such as a parking session.
 */
final class UsageReceiptItem implements TaxableInterface
{
    use CanFillFromArray;

    private ?string $user = null;

    private ?string $identifier = null;

    private ?CarbonImmutable $startDate = null;

    private ?CarbonImmutable $endDate = null;

    private ?string $category = null;

    private ?int $priceCents = null;

    /**
     * Set the name of the user of the session.
     *
     * @return $this
     */
    public function user(?string $user): static
    {
        $this->user = Text::clean($user);

        return $this;
    }

    /**
     * Set what was used, such as a license plate.
     *
     * @return $this
     */
    public function identifier(?string $identifier): static
    {
        $this->identifier = Text::clean($identifier);

        return $this;
    }

    /**
     * Set the start of the session. Strings are read in the app timezone.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException when the string is not a valid date
     */
    public function startDate(DateTimeInterface|string|null $startDate): static
    {
        $this->startDate = Dates::parse($startDate, 'start date', InvalidUsageReceiptException::class);

        return $this;
    }

    /**
     * Set the end of the session. Strings are read in the app timezone.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException when the string is not a valid date
     */
    public function endDate(DateTimeInterface|string|null $endDate): static
    {
        $this->endDate = Dates::parse($endDate, 'end date', InvalidUsageReceiptException::class);

        return $this;
    }

    /**
     * Set the category of the session, such as a zone.
     *
     * @return $this
     */
    public function category(?string $category): static
    {
        $this->category = Text::clean($category);

        return $this;
    }

    /**
     * Set the price of the session, with at most 2 decimals.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException when the price is not a number with at most 2 decimals
     */
    public function price(float|int|string|null $price): static
    {
        $this->priceCents = Money::toCents($price, 'price', InvalidUsageReceiptException::class);

        return $this;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getStartDate(): ?CarbonImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?CarbonImmutable
    {
        return $this->endDate;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getPrice(): ?float
    {
        return $this->priceCents === null ? null : Money::toFloat($this->priceCents);
    }

    public function getPriceCents(): ?int
    {
        return $this->priceCents;
    }

    /**
     * Sessions carry no tax information.
     */
    public function getTaxPercentage(): ?float
    {
        return null;
    }

    /**
     * Get the price in cents, used for the total of a receipt without lines.
     */
    public function getTotalCents(): int
    {
        return $this->priceCents ?? 0;
    }

    /**
     * Validate the session.
     *
     * @param  int|null  $index  the position of the session on the receipt, used in the message
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException
     */
    public function validate(?int $index = null): void
    {
        $prefix = $index === null ? 'Item' : "Item at index {$index}";

        foreach (['a user' => $this->user, 'an identifier' => $this->identifier, 'a category' => $this->category] as $field => $value) {
            if ($value === null) {
                throw new InvalidUsageReceiptException("{$prefix} must have {$field}");
            }
        }

        if ($this->startDate === null) {
            throw new InvalidUsageReceiptException("{$prefix} must have a start date");
        }

        if ($this->endDate === null) {
            throw new InvalidUsageReceiptException("{$prefix} must have an end date");
        }

        if ($this->endDate->lt($this->startDate)) {
            throw new InvalidUsageReceiptException("{$prefix} must not end before it starts");
        }

        if ($this->priceCents === null) {
            throw new InvalidUsageReceiptException("{$prefix} must have a price");
        }

        if ($this->priceCents < 0) {
            throw new InvalidUsageReceiptException("{$prefix} must not have a negative price");
        }
    }

    /**
     * Convert the session to an array that make() accepts.
     *
     * @return array{user: string|null, identifier: string|null, start_date: string|null, end_date: string|null, category: string|null, price: float|null}
     */
    public function toArray(): array
    {
        return [
            'user' => $this->user,
            'identifier' => $this->identifier,
            'start_date' => $this->startDate?->toIso8601String(),
            'end_date' => $this->endDate?->toIso8601String(),
            'category' => $this->category,
            'price' => $this->getPrice(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function fillable(): array
    {
        return [
            'user' => 'user',
            'identifier' => 'identifier',
            'start_date' => 'startDate',
            'end_date' => 'endDate',
            'category' => 'category',
            'price' => 'price',
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
