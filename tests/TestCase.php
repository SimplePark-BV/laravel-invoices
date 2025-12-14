<?php

namespace Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SimpleParkBv\Invoices\InvoiceServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            InvoiceServiceProvider::class,
        ];
    }
}
