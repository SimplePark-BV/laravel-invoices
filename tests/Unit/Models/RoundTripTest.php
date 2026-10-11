<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Enums\PaymentMethod;
use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\UsageReceipt;
use Tests\TestCase;
use Tests\Traits\CreatesTestInvoices;
use Tests\Traits\CreatesTestReceipts;

final class RoundTripTest extends TestCase
{
    use CreatesTestInvoices;
    use CreatesTestReceipts;

    #[Test]
    public function an_empty_invoice_round_trips(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        $copy = Invoice::make($invoice->toArray());

        // assert
        $this->assertEquals($invoice, $copy);
    }

    #[Test]
    public function a_full_invoice_round_trips(): void
    {
        // arrange
        $invoice = $this->createFullInvoice()
            ->language('en')
            ->dueDate('2026-11-01')
            ->paymentTermsDays(14)
            ->paymentMethod(PaymentMethod::DirectDebit)
            ->paid()
            ->footerMessage('Paid with iDEAL: :amount & thanks <3')
            ->conceptFooterMessage('Not final yet')
            ->dateFormat('j F Y')
            ->expectedTotal(46.89);

        // act
        $copy = Invoice::make($invoice->toArray());

        // assert
        $this->assertEquals($invoice, $copy);
        $this->assertSame($invoice->toArray(), $copy->toArray());
        $this->assertSame($invoice->getFooterMessage(), $copy->getFooterMessage());
    }

    #[Test]
    public function a_concept_invoice_keeps_its_footer_after_a_round_trip(): void
    {
        // arrange
        $invoice = $this->createInvoice();

        // act
        $copy = Invoice::make($invoice->toArray());

        // assert
        $this->assertStringContainsString('CONCEPT', $copy->getFooterMessage());
    }

    #[Test]
    public function an_empty_receipt_round_trips(): void
    {
        // arrange
        $receipt = UsageReceipt::make();

        // act
        $copy = UsageReceipt::make($receipt->toArray());

        // assert
        $this->assertEquals($receipt, $copy);
    }

    #[Test]
    public function a_full_receipt_round_trips(): void
    {
        // arrange
        $receipt = $this->createFullReceipt()
            ->title('Parking receipt')
            ->dateTimeFormat('H:i')
            ->expectedTotal(12.75);

        // act
        $copy = UsageReceipt::make($receipt->toArray());

        // assert
        $this->assertEquals($receipt, $copy);
        $this->assertSame($receipt->toArray(), $copy->toArray());
    }

    #[Test]
    public function a_round_trip_through_json_renders_the_same_html(): void
    {
        // arrange
        $invoice = $this->createFullInvoice(['logo' => null]);
        $receipt = $this->createFullReceipt(['logo' => null]);

        // act
        $invoiceCopy = Invoice::make($this->throughJson($invoice->toArray()));
        $receiptCopy = UsageReceipt::make($this->throughJson($receipt->toArray()));

        // assert
        $this->assertSame($invoice->toHtml(), $invoiceCopy->toHtml());
        $this->assertSame($receipt->toHtml(), $receiptCopy->toHtml());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function throughJson(array $data): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) json_encode($data), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
