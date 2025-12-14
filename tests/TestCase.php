<?php

namespace Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SimpleParkBv\LaravelInvoices\LaravelInvoicesServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelInvoicesServiceProvider::class,
        ];
    }
}

