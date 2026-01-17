<?php

namespace SimpleParkBv\Invoices\Models;

use RuntimeException;

/**
 * Class Seller
 *
 * @property string|null $registrationNumber
 * @property string|null $taxId
 * @property string|null $bankAccount
 */
final class Seller extends Party
{
    public ?string $registrationNumber;

    public ?string $taxId;

    public ?string $bankAccount;

    public function __construct()
    {
        $seller = config('invoices.seller');

        $this->name = $seller['name'] ?? throw new RuntimeException('Seller name is required');

        $this->address = $seller['address'] ?? null;
        $this->postalCode = $seller['postal_code'] ?? null;
        $this->city = $seller['city'] ?? null;
        $this->country = $seller['country'] ?? null;
        $this->email = $seller['email'] ?? null;
        $this->phone = null;
        $this->website = null;
        $this->registrationNumber = $seller['registration_number'] ?? null;
        $this->taxId = $seller['tax_id'] ?? null;
        $this->bankAccount = $seller['bank_account'] ?? null;
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * Get the seller's registration number.
     */
    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber ?? null;
    }

    /**
     * Get the seller's tax ID.
     */
    public function getTaxId(): ?string
    {
        return $this->taxId ?? null;
    }

    /**
     * Get the seller's bank account.
     */
    public function getBankAccount(): ?string
    {
        return $this->bankAccount ?? null;
    }
}
