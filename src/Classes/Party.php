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
}
