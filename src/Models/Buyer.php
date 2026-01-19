<?php

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Contracts\PartyInterface;

/**
 * Class Buyer
 */
final class Buyer extends Party
{
    public static function make(): self
    {
        return new self;
    }

    /**
     * Create a Buyer from a PartyInterface.
     */
    public static function fromParty(PartyInterface $party): self
    {
        $buyer = self::make();
        $data = $party->toArray();

        $buyer->name = $data['name'];
        $buyer->address = $data['address'];
        $buyer->city = $data['city'];
        $buyer->postalCode = $data['postal_code'];
        $buyer->country = $data['country'];
        $buyer->email = $data['email'];
        $buyer->phone = $data['phone'];
        $buyer->website = $data['website'];

        return $buyer;
    }
}
