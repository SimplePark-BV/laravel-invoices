<?php

namespace SimpleParkBv\Invoices\Tests;

use PHPUnit\Framework\Attributes\Test;
use Orchestra\Testbench\TestCase;
use SimpleParkBv\Invoices\Invoice;
use SimpleParkBv\Invoices\InvoiceItem;
use SimpleParkBv\Invoices\InvoiceServiceProvider;
use SimpleParkBv\Invoices\Buyer;

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

        $items = [
            [
                'title' => 'Parkeersessie in zone 26258',
                'description' => 'Van 7 december 2025 15:46 tot 17:12 met 91-FS-VV',
                'quantity' => 1,
                'unit_price' => 12.05,
                'tax_percentage' => 0,
            ],
            [
                'title' => 'Parkeersessie in zone 26258',
                'description' => 'Van 7 december 2025 15:46 tot 17:12 met 91-FS-VV',
                'quantity' => 1,
                'unit_price' => 12.05,
                'tax_percentage' => 0,
            ],
            [
                'title' => 'Servicekosten',
                'quantity' => 2,
                'unit_price' => 0.39,
                'tax_percentage' => 21,
            ]
        ];

        $invoice = Invoice::make();

        foreach ($items as $array) {
            $item = InvoiceItem::make();

            $item->title = $array['title'];
            $item->description = $array['description'] ?? null;
            $item->quantity = $array['quantity'];
            $item->unit_price = $array['unit_price'];
            $item->tax_percentage = $array['tax_percentage'];

            $invoice->addItem($item);
        }

        $buyer = Buyer::make();

        $buyer->name = 'Test Buyer';
        $buyer->address = 'Test Address';
        $buyer->postal_code = '1234AB';
        $buyer->city = 'Test City';
        $buyer->email = 'test@example.com';
        $buyer->phone = null;

        // act
        $invoice->buyer = $buyer;
        $invoice->render();

        $outputPath = __DIR__.'/../test-output.pdf';
        $invoice->pdf->save($outputPath);

        // assert
        $this->assertFileExists($outputPath);
    }
}
