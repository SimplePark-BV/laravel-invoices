<?php

namespace SimpleParkBv\Invoices;

/**
 * Class Buyer
 */
final class Buyer extends Party
{
    public static function make(): self
    {
        return new self;
    }
}
