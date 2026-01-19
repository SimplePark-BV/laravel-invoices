<?php

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Contracts\PartyInterface;

/**
 * Class Party
 *
 * @property string $name
 * @property string|null $address
 * @property string|null $city
 * @property string|null $postalCode
 * @property string|null $country
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 */
abstract class Party implements PartyInterface
{
    protected string $name;

    protected ?string $address = null;

    protected ?string $city = null;

    protected ?string $postalCode = null;

    protected ?string $country = null;

    protected ?string $email = null;

    protected ?string $phone = null;

    protected ?string $website = null;

    /**
     * Set the party's name.
     *
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the party's address.
     *
     * @return $this
     */
    public function address(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Set the party's city.
     *
     * @return $this
     */
    public function city(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Set the party's postal code.
     *
     * @return $this
     */
    public function postalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Set the party's country.
     *
     * @return $this
     */
    public function country(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Set the party's email address.
     *
     * @return $this
     */
    public function email(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Set the party's phone number.
     *
     * @return $this
     */
    public function phone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Set the party's website URL.
     *
     * @return $this
     */
    public function website(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

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
        return $this->address ?? null;
    }

    /**
     * Get the party's city.
     */
    public function getCity(): ?string
    {
        return $this->city ?? null;
    }

    /**
     * Get the party's postal code.
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode ?? null;
    }

    /**
     * Get the party's country.
     */
    public function getCountry(): ?string
    {
        return $this->country ?? null;
    }

    /**
     * Get the party's email address.
     */
    public function getEmail(): ?string
    {
        return $this->email ?? null;
    }

    /**
     * Get the party's phone number.
     */
    public function getPhone(): ?string
    {
        return $this->phone ?? null;
    }

    /**
     * Get the party's website URL.
     */
    public function getWebsite(): ?string
    {
        return $this->website ?? null;
    }

    /**
     * Check if the party has any address information.
     */
    public function hasAddress(): bool
    {
        return ! empty($this->address) || ! empty($this->postalCode) || ! empty($this->city);
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
            'address' => $this->address ?? null,
            'city' => $this->city ?? null,
            'postal_code' => $this->postalCode ?? null,
            'country' => $this->country ?? null,
            'email' => $this->email ?? null,
            'phone' => $this->phone ?? null,
            'website' => $this->website ?? null,
        ];
    }
}
