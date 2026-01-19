<?php

namespace SimpleParkBv\Invoices\Models;

use Illuminate\Support\Carbon;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Services\CurrencyFormatter;

/**
 * Class ReceiptItem
 *
 * Represents a single item/session in a usage receipt
 *
 * @property string $user
 * @property string $identifier
 * @property \Illuminate\Support\Carbon $startDate
 * @property \Illuminate\Support\Carbon $endDate
 * @property string $category
 * @property float $price
 */
final class ReceiptItem
{
    use CanFillFromArray;

    public ?string $user = null;

    public ?string $identifier = null;

    public ?Carbon $startDate = null;

    public ?Carbon $endDate = null;

    public ?string $category = null;

    public ?float $price = null;

    public string $dateFormat = 'd-m-Y H:i';

    public static function make(): self
    {
        return new self;
    }

    /**
     * Create a receipt item from an array of data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::make()->fill($data);
    }

    /**
     * Set the user name.
     *
     * @return $this
     */
    public function user(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the identifier.
     *
     * @return $this
     */
    public function identifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Set the start date.
     *
     * @return $this
     */
    public function startDate(Carbon|string $startDate): self
    {
        if (is_string($startDate)) {
            $this->startDate = Carbon::parse($startDate);
        } else {
            $this->startDate = $startDate;
        }

        return $this;
    }

    /**
     * Set the end date.
     *
     * @return $this
     */
    public function endDate(Carbon|string $endDate): self
    {
        if (is_string($endDate)) {
            $this->endDate = Carbon::parse($endDate);
        } else {
            $this->endDate = $endDate;
        }

        return $this;
    }

    /**
     * Set the category.
     *
     * @return $this
     */
    public function category(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Set the price.
     *
     * @return $this
     */
    public function price(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get the user name.
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * Get the identifier.
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Get the start date.
     */
    public function getStartDate(): Carbon
    {
        return $this->startDate;
    }

    /**
     * Get the end date.
     */
    public function getEndDate(): Carbon
    {
        return $this->endDate;
    }

    /**
     * Get the category.
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Get the price.
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * Get the formatted start date.
     */
    public function getFormattedStartDate(): string
    {
        return $this->startDate->format($this->dateFormat);
    }

    /**
     * Get the formatted end date.
     */
    public function getFormattedEndDate(): string
    {
        return $this->endDate->format($this->dateFormat);
    }

    /**
     * Get the formatted price with currency symbol.
     */
    public function getFormattedPrice(): string
    {
        return CurrencyFormatter::format($this->price);
    }

    /**
     * Validate the receipt item.
     *
     * @throws \RuntimeException
     */
    public function validate(?int $index = null): void
    {
        $prefix = $index !== null ? "Item at index {$index}" : 'Item';

        // validate required string fields
        foreach (['user', 'identifier', 'category'] as $field) {
            if (empty($this->$field)) {
                throw new \RuntimeException("{$prefix} must have a {$field}");
            }
        }

        // validate required date fields
        if ($this->startDate === null) {
            throw new \RuntimeException("{$prefix} must have a start date");
        }

        if ($this->endDate === null) {
            throw new \RuntimeException("{$prefix} must have an end date");
        }

        // validate date logic
        if ($this->endDate->lt($this->startDate)) {
            throw new \RuntimeException("{$prefix} end date must be after start date");
        }

        // validate price
        if ($this->price === null) {
            throw new \RuntimeException("{$prefix} must have a price");
        }

        if ($this->price < 0) {
            throw new \RuntimeException("{$prefix} price cannot be negative");
        }
    }

    /**
     * Convert the receipt item to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user' => $this->user,
            'identifier' => $this->identifier,
            'start_date' => $this->startDate->toIso8601String(),
            'end_date' => $this->endDate->toIso8601String(),
            'category' => $this->category,
            'price' => $this->price,
        ];
    }
}
