<?php

namespace SimpleParkBv\Invoices;

/**
 * Class Seller
 * 
 * @property string|null $kvk
 * @property string|null $btw
 * @property string|null $iban
 */
final class Seller extends Party
{
    public string|null $kvk;

    public string|null $btw;

    public string|null $iban;

    public function __construct()
    {
        $seller = config('invoices.seller');

        $this->name = $seller['name'] ?? throw new \RuntimeException('Seller name is required');

        $this->address = $seller['address'] ?? null;
        $this->postal_code = $seller['postal_code'] ?? null;
        $this->city = $seller['city'] ?? null;
        $this->country = $seller['country'] ?? null;
        $this->email = $seller['email'] ?? null;
        $this->phone = null;
        $this->website = null;
        $this->kvk = $seller['kvk'] ?? null;
        $this->btw = $seller['btw'] ?? null;
        $this->iban = $seller['iban'] ?? null;
    }

    public static function make(): self
    {
        return new self;
    }
}