<?php

namespace SimpleParkBv\Invoices;

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
abstract class Party
{
    public string $name;

    public string|null $address;

    public string|null $city;

    public string|null $postal_code;

    public string|null $country;

    public string|null $email;

    public string|null $phone;

    public string|null $website;

    public function __construct()
    {
        // todo
    }
}