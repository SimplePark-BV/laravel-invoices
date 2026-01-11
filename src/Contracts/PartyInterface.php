<?php

namespace SimpleParkBv\Invoices\Contracts;

/**
 * Interface for party (buyer/seller) implementations.
 *
 * Defines the contract for invoice parties, ensuring all implementations
 * provide access to common party information such as name, address, and contact details.
 */
interface PartyInterface
{
    /**
     * Get the party's name.
     */
    public function getName(): string;

    /**
     * Get the party's address.
     */
    public function getAddress(): ?string;

    /**
     * Get the party's city.
     */
    public function getCity(): ?string;

    /**
     * Get the party's postal code.
     */
    public function getPostalCode(): ?string;

    /**
     * Get the party's country.
     */
    public function getCountry(): ?string;

    /**
     * Get the party's email address.
     */
    public function getEmail(): ?string;

    /**
     * Get the party's phone number.
     */
    public function getPhone(): ?string;

    /**
     * Get the party's website URL.
     */
    public function getWebsite(): ?string;

    /**
     * Convert the party to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
