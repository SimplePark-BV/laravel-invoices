<?php

namespace Tests\Feature;

use Illuminate\Mail\Attachment;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\UsageReceipt;
use Tests\TestCase;

/**
 * The examples of the README, so they keep working.
 */
final class ReadmeExamplesTest extends TestCase
{
    #[Test]
    public function the_invoice_example_works(): void
    {
        // act
        $invoice = Invoice::make()
            ->language('nl')
            ->serial('2026.0001')
            ->date('2026-10-11')
            ->buyer([
                'name' => 'Jan de Vries',
                'address' => 'Keizersgracht 123',
                'postal_code' => '1015 CJ',
                'city' => 'Amsterdam',
                'email' => 'jan@example.com',
            ])
            ->items([
                ['title' => 'Parking', 'description' => 'Zone 1001', 'quantity' => 2, 'unit_price' => 12.10, 'tax_percentage' => 21],
                ['title' => 'Discount', 'unit_price' => -2.00, 'tax_percentage' => 21],
            ]);

        $response = $invoice->stream();

        // assert
        $this->assertSame('inline; filename=factuur-2026.0001.pdf', $response->headers->get('Content-Disposition'));
        $this->assertSame(22.20, $invoice->getTotal());
    }

    #[Test]
    public function the_usage_receipt_example_works(): void
    {
        // act
        $receipt = UsageReceipt::make()
            ->language('nl')
            ->date('2026-10-11 14:30')
            ->documentId('PS-2026-000123')
            ->userId('USR-000042')
            ->buyer(['name' => 'Jan de Vries', 'email' => 'jan@example.com'])
            ->items([
                [
                    'user' => 'Jan de Vries',
                    'identifier' => 'AB-123-C',
                    'start_date' => '2026-10-02 10:00',
                    'end_date' => '2026-10-02 12:00',
                    'category' => 'Zone 1001',
                    'price' => 12.50,
                ],
            ])
            ->note('Thank you for parking.');

        $response = $receipt->download();

        $receipt->lines([
            ['label' => 'Parking costs', 'amount' => 12.50],
            ['label' => 'Service fee', 'amount' => 0.35, 'tax_percentage' => 21],
            ['label' => 'Discount', 'amount' => -1.00, 'tax_percentage' => 21],
        ]);

        // assert
        $this->assertSame('attachment; filename=gebruiksbevestiging-PS-2026-000123.pdf', $response->headers->get('Content-Disposition'));
        $this->assertSame(11.85, $receipt->getTotal());
        $this->assertStringContainsString('21% btw', $receipt->toHtml());
    }

    #[Test]
    public function the_array_example_round_trips(): void
    {
        // act
        $invoice = Invoice::make([
            'buyer' => ['name' => 'Jan de Vries'],
            'serial' => '2026.0001',
            'date' => '2026-10-11',
            'items' => [
                ['title' => 'Parking', 'unit_price' => 12.10, 'tax_percentage' => 21],
            ],
        ]);

        // assert
        $this->assertEquals($invoice, Invoice::make($invoice->toArray()));
    }

    #[Test]
    public function the_mail_attachment_example_works(): void
    {
        // arrange
        $invoice = Invoice::make([
            'buyer' => ['name' => 'Jan de Vries'],
            'items' => [['title' => 'Parking', 'unit_price' => 12.10]],
        ]);

        // act
        $attachment = Attachment::fromData(fn () => $invoice->toPdf(), $invoice->getFilename())->withMime('application/pdf');

        // assert
        $this->assertSame('factuur-CONCEPT.pdf', $attachment->as);
        $this->assertSame('application/pdf', $attachment->mime);
    }
}
