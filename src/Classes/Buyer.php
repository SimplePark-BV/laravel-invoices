<?php

namespace SimpleParkBv\Invoices;

/**
 * Class Buyer
 */
final class Buyer extends Party
{
    public function __construct()
    {
        // todo
    }

    public static function make(): self
    {
        return new self;
    }
}
