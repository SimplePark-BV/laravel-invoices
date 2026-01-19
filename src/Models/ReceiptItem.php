<?php

namespace SimpleParkBv\Invoices\Models;

use Illuminate\Support\Carbon;
use SimpleParkBv\Invoices\Services\CurrencyFormatter;

/**
 * Class ReceiptItem
 *
 * Represents a single parking session in a usage receipt
 *
 * @property string $user
 * @property string $licensePlate
 * @property \Illuminate\Support\Carbon $startDate
 * @property \Illuminate\Support\Carbon $endDate
 * @property string $zone
 * @property float $price
 */
final class ReceiptItem
{
    public string $user;

    public string $licensePlate;

    public Carbon $startDate;

    public Carbon $endDate;

    public string $zone;

    public float $price;

    public string $date_format = 'd-m-Y H:i';

    public static function make(): self
    {
        return new self;
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
     * Set the license plate.
     *
     * @return $this
     */
    public function licensePlate(string $licensePlate): self
    {
        $this->licensePlate = $licensePlate;

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
     * Set the zone.
     *
     * @return $this
     */
    public function zone(string $zone): self
    {
        $this->zone = $zone;

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
     * Get the license plate.
     */
    public function getLicensePlate(): string
    {
        return $this->licensePlate;
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
     * Get the zone.
     */
    public function getZone(): string
    {
        return $this->zone;
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
        return $this->startDate->format($this->date_format);
    }

    /**
     * Get the formatted end date.
     */
    public function getFormattedEndDate(): string
    {
        return $this->endDate->format($this->date_format);
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

        if (empty($this->user)) {
            throw new \RuntimeException("{$prefix} must have a user");
        }

        if (empty($this->licensePlate)) {
            throw new \RuntimeException("{$prefix} must have a license plate");
        }

        if (! isset($this->startDate)) {
            throw new \RuntimeException("{$prefix} must have a start date");
        }

        if (! isset($this->endDate)) {
            throw new \RuntimeException("{$prefix} must have an end date");
        }

        if ($this->endDate->lt($this->startDate)) {
            throw new \RuntimeException("{$prefix} end date must be after start date");
        }

        if (empty($this->zone)) {
            throw new \RuntimeException("{$prefix} must have a zone");
        }

        if (! isset($this->price)) {
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
            'license_plate' => $this->licensePlate,
            'start_date' => $this->startDate->toIso8601String(),
            'end_date' => $this->endDate->toIso8601String(),
            'zone' => $this->zone,
            'price' => $this->price,
        ];
    }
}
