<?php

namespace Tests\Unit\Models\Traits;

use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;
use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\Invoice;
use SimpleParkBv\Invoices\Models\UsageReceipt;
use Tests\TestCase;
use Tests\Traits\CreatesTestInvoices;
use Tests\Traits\CreatesTestReceipts;

final class ValidatesExpectedTotalTest extends TestCase
{
    use CreatesTestInvoices;
    use CreatesTestReceipts;

    #[Test]
    public function logs_error_when_invoice_expected_total_differs(): void
    {
        // arrange
        Log::shouldReceive('error')
            ->once()
            ->with(
                'Expected total differs from calculated total',
                Mockery::on(function ($context) {
                    return isset($context['expected_total'])
                        && isset($context['actual_total'])
                        && isset($context['difference'])
                        && abs($context['expected_total'] - 100.00) < 0.01
                        && abs($context['actual_total'] - 10.00) < 0.01;
                })
            );

        $invoice = Invoice::make();
        $invoice->buyer(Buyer::make(['name' => 'Test Buyer']));
        $invoice->addItem($this->createInvoiceItem(['unit_price' => 10.00]));
        $invoice->expectedTotal(100.00);

        // act
        $this->callValidateExpectedTotal($invoice);
    }

    #[Test]
    public function logs_error_when_usage_receipt_expected_total_differs(): void
    {
        // arrange
        Log::shouldReceive('error')
            ->once()
            ->with(
                'Expected total differs from calculated total',
                Mockery::on(function ($context) {
                    return isset($context['expected_total'])
                        && isset($context['actual_total'])
                        && isset($context['difference'])
                        && abs($context['expected_total'] - 50.00) < 0.01
                        && abs($context['actual_total'] - 5.50) < 0.01;
                })
            );

        $receipt = UsageReceipt::make();
        $receipt->buyer(Buyer::make(['name' => 'Test Buyer']));
        $receipt->addItem($this->createReceiptItem(['price' => 5.50]));
        $receipt->documentId('DOC-123');
        $receipt->expectedTotal(50.00);

        // act
        $this->callValidateExpectedTotal($receipt);
    }

    #[Test]
    public function throws_invalid_invoice_exception_when_throw_is_true(): void
    {
        // arrange
        Log::shouldReceive('error')
            ->once()
            ->with('Expected total differs from calculated total', Mockery::any());

        $invoice = Invoice::make();
        $invoice->buyer(Buyer::make(['name' => 'Test Buyer']));
        $invoice->addItem($this->createInvoiceItem(['unit_price' => 10.00]));
        $invoice->expectedTotal(100.00, true);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Expected total differs from calculated total');

        // act
        $this->callValidateExpectedTotal($invoice);
    }

    #[Test]
    public function throws_invalid_usage_receipt_exception_when_throw_is_true(): void
    {
        // arrange
        Log::shouldReceive('error')
            ->once()
            ->with('Expected total differs from calculated total', Mockery::any());

        $receipt = UsageReceipt::make();
        $receipt->buyer(Buyer::make(['name' => 'Test Buyer']));
        $receipt->addItem($this->createReceiptItem(['price' => 5.50]));
        $receipt->documentId('DOC-123');
        $receipt->expectedTotal(50.00, true);

        // assert
        $this->expectException(InvalidUsageReceiptException::class);
        $this->expectExceptionMessage('Expected total differs from calculated total');

        // act
        $this->callValidateExpectedTotal($receipt);
    }

    /**
     * Call the protected validateExpectedTotal method using reflection.
     */
    private function callValidateExpectedTotal(Invoice|UsageReceipt $model): void
    {
        $method = new ReflectionMethod($model, 'validateExpectedTotal');
        $method->setAccessible(true);
        $method->invoke($model);
    }
}
