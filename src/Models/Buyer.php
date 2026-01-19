<?php

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Contracts\PartyInterface;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;

/**
 * Class Buyer
 */
final class Buyer extends Party
{
    use CanFillFromArray;

    public static function make(): self
    {
        return new self;
    }

    /**
     * Create a Buyer from a PartyInterface.
     */
    public static function fromParty(PartyInterface $party): self
    {
        return self::make()->fill([
            'name' => $party->getName(),
            'address' => $party->getAddress(),
            'city' => $party->getCity(),
            'postalCode' => $party->getPostalCode(),
            'country' => $party->getCountry(),
            'email' => $party->getEmail(),
            'phone' => $party->getPhone(),
            'website' => $party->getWebsite(),
        ]);
    }
}
