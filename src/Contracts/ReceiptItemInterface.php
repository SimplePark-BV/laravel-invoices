<?php

namespace SimpleParkBv\Invoices\Contracts;

use Illuminate\Support\Carbon;

/**
 * Interface for receipt item implementations.
 */
interface ReceiptItemInterface
{
    /**
     * Create a new receipt item instance.
     *
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data = []): self;

    /**
     * Set the user name.
     */
    public function user(string $user): self;

    /**
     * Set the identifier.
     */
    public function identifier(string $identifier): self;

    /**
     * Set the start date.
     */
    public function startDate(Carbon|string $startDate): self;

    /**
     * Set the end date.
     */
    public function endDate(Carbon|string $endDate): self;

    /**
     * Set the category.
     */
    public function category(string $category): self;

    /**
     * Set the price.
     */
    public function price(float $price): self;

    /**
     * Get the user name.
     */
    public function getUser(): string;

    /**
     * Get the identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get the start date.
     */
    public function getStartDate(): Carbon;

    /**
     * Get the end date.
     */
    public function getEndDate(): Carbon;

    /**
     * Get the category.
     */
    public function getCategory(): string;

    /**
     * Get the price.
     */
    public function getPrice(): float;

    /**
     * Get the formatted start date.
     */
    public function getFormattedStartDate(): string;

    /**
     * Get the formatted end date.
     */
    public function getFormattedEndDate(): string;

    /**
     * Get the formatted price with currency symbol.
     */
    public function getFormattedPrice(): string;

    /**
     * Validate the receipt item.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidReceiptItemException
     */
    public function validate(?int $index = null): void;

    /**
     * Convert the receipt item to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
