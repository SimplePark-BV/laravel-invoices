<?php

namespace SimpleParkBv\LaravelInvoices;

use Illuminate\Support\Facades\Log;

/**
 * Class Invoice
 */
final class Invoice
{
    public function __construct()
    {
        // todo
    }

    public static function make(): self
    {
        return new self;
    }

    public function download(): void
    {
        // todo

        Log::info('#[Test] !! Downloading invoice !!');
    }
}
