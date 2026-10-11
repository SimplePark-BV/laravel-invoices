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

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.timezone', 'Europe/Amsterdam');
        date_default_timezone_set('Europe/Amsterdam');

        $app['config']->set('invoices.seller', [
            'name' => 'Test Seller B.V.',
            'address' => 'Teststraat 1',
            'postal_code' => '1234 AB',
            'city' => 'Teststad',
            'country' => 'Nederland',
            'email' => 'info@seller.test',
            'phone' => null,
            'website' => 'www.seller.test',
            'registration_number' => '12345678',
            'tax_id' => 'NL123456789B01',
            'bank_account' => 'NL00 TEST 0123 4567 89',
        ]);

        // keep the dompdf font cache out of the vendor directory
        $fontDirectory = sys_get_temp_dir().'/laravel-invoices-tests/fonts';
        $app['config']->set('dompdf.options.font_dir', $fontDirectory);
        $app['config']->set('dompdf.options.font_cache', $fontDirectory);
    }
}
