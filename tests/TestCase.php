<?php

namespace Tests;

use Barryvdh\DomPDF\ServiceProvider as DomPDFServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SimpleParkBv\Invoices\InvoiceServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DomPDFServiceProvider::class,
            InvoiceServiceProvider::class,
        ];
    }
}
