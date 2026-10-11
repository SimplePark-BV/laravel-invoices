<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Exceptions\InvalidDocumentException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Support\Text;

/**
 * A party on a document: the buyer or the seller.
 *
 * Every field is optional here; the documents validate what they need. Texts are trimmed, and empty texts become null.
 *
 * @phpstan-consistent-constructor
 */
abstract class Party
{
    use CanFillFromArray;

    protected ?string $name = null;

    protected ?string $address = null;

    protected ?string $postalCode = null;

    protected ?string $city = null;

    protected ?string $country = null;

    protected ?string $email = null;

    protected ?string $phone = null;

    protected ?string $website = null;

    /**
     * Set the name.
     *
     * @return $this
     */
    public function name(?string $name): static
    {
        $this->name = Text::clean($name);

        return $this;
    }

    /**
     * Set the street address.
     *
     * @return $this
     */
    public function address(?string $address): static
    {
        $this->address = Text::clean($address);

        return $this;
    }

    /**
     * Set the postal code.
     *
     * @return $this
     */
    public function postalCode(?string $postalCode): static
    {
        $this->postalCode = Text::clean($postalCode);

        return $this;
    }

    /**
     * Set the city.
     *
     * @return $this
     */
    public function city(?string $city): static
    {
        $this->city = Text::clean($city);

        return $this;
    }

    /**
     * Set the country.
     *
     * @return $this
     */
    public function country(?string $country): static
    {
        $this->country = Text::clean($country);

        return $this;
    }

    /**
     * Set the email address.
     *
     * @return $this
     */
    public function email(?string $email): static
    {
        $this->email = Text::clean($email);

        return $this;
    }

    /**
     * Set the phone number.
     *
     * @return $this
     */
    public function phone(?string $phone): static
    {
        $this->phone = Text::clean($phone);

        return $this;
    }

    /**
     * Set the website.
     *
     * @return $this
     */
    public function website(?string $website): static
    {
        $this->website = Text::clean($website);

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * Check if the party has any address information.
     */
    public function hasAddress(): bool
    {
        return $this->address !== null || $this->postalCode !== null || $this->city !== null || $this->country !== null;
    }

    /**
     * Convert the party to an array that make() accepts.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->address,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->country,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function fillable(): array
    {
        return [
            'name' => 'name',
            'address' => 'address',
            'postal_code' => 'postalCode',
            'city' => 'city',
            'country' => 'country',
            'email' => 'email',
            'phone' => 'phone',
            'website' => 'website',
        ];
    }

    /**
     * @return class-string<\SimpleParkBv\Invoices\Exceptions\InvalidDocumentException>
     */
    protected static function invalidDataException(): string
    {
        return InvalidDocumentException::class;
    }
}
