<?php

namespace SimpleParkBv\Invoices;

use SimpleParkBv\Invoices\Contracts\PartyInterface;

/**
 * Class Party
 *
 * @property string $name
 * @property string|null $address
 * @property string|null $city
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 */
abstract class Party implements PartyInterface
{
    public string $name;

    public ?string $address;

    public ?string $city;

    public ?string $postal_code;

    public ?string $country;

    public ?string $email;

    public ?string $phone;

    public ?string $website;

    /**
     * Get the party's name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the party's address.
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Get the party's city.
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Get the party's postal code.
     */
    public function getPostalCode(): ?string
    {
        return $this->postal_code;
    }

    /**
     * Get the party's country.
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * Get the party's email address.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get the party's phone number.
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Get the party's website URL.
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * Convert the party to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
        ];
    }
}
