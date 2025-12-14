<?php

namespace SimpleParkBv\Invoices\Tests;

use PHPUnit\Framework\Attributes\Test;
use Orchestra\Testbench\TestCase;
use SimpleParkBv\Invoices\Invoice;
use SimpleParkBv\Invoices\InvoiceItem;
use SimpleParkBv\Invoices\InvoiceServiceProvider;

// manually require classes since autoload doesn't map Classes/ subdirectory
require_once __DIR__.'/../src/Classes/Party.php';
require_once __DIR__.'/../src/Classes/Buyer.php';
require_once __DIR__.'/../src/Classes/Seller.php';
require_once __DIR__.'/../src/Classes/InvoiceItem.php';
require_once __DIR__.'/../src/Classes/Invoice.php';

class PdfTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \Barryvdh\DomPDF\ServiceProvider::class,
            InvoiceServiceProvider::class,
        ];
    }

    #[Test]
    public function it_can_save_an_invoice_locally(): void
    {
        // arrange
        config()->set('invoices.default_payment_terms_days', 14);
        config()->set('invoices.pdf.paper_size', 'a4');
        config()->set('invoices.pdf.orientation', 'portrait');

        $item = InvoiceItem::make();
        $item->description = 'Test Item';
        $item->quantity = 1;
        $item->unit_price = 100;
        $item->tax_percentage = 21;

        $buyer = new \SimpleParkBv\Invoices\Buyer();
        $buyer->name = 'Test Buyer';
        $buyer->address = 'Test Address';
        $buyer->postal_code = '1234AB';
        $buyer->city = 'Test City';
        $buyer->email = 'test@example.com';
        $buyer->phone = null;

        // act
        $invoice = Invoice::make()
            ->addItem($item);
        $invoice->buyer = $buyer;
        $invoice->render();

        $outputPath = __DIR__.'/../test-output.pdf';
        $invoice->pdf->save($outputPath);

        // assert
        $this->assertFileExists($outputPath);
    }
}
