<?php

namespace Tests\Feature\Invoice;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\ExpectedTotalMismatchException;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Exceptions\InvoicesException;
use SimpleParkBv\Invoices\Models\Invoice;
use Tests\TestCase;
use Tests\Traits\CreatesTestInvoices;

final class ValidationTest extends TestCase
{
    use CreatesTestInvoices;

    #[Test]
    #[DataProvider('outputs')]
    public function every_output_validates_the_invoice_first(string $output): void
    {
        // arrange
        $invoice = Invoice::make(['items' => [['title' => 'Parking', 'unit_price' => 10]]]);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('An invoice must have a buyer');

        // act
        $invoice->{$output}();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function outputs(): array
    {
        return [
            'html' => ['toHtml'],
            'pdf' => ['toPdf'],
            'download' => ['download'],
            'stream' => ['stream'],
        ];
    }

    #[Test]
    #[DataProvider('outputs')]
    public function every_output_checks_the_expected_total(string $output): void
    {
        // arrange
        $invoice = $this->createInvoice(['expected_total' => 10.01]);

        // assert
        $this->expectException(ExpectedTotalMismatchException::class);

        // act
        $invoice->{$output}();
    }

    #[Test]
    public function changes_after_an_output_are_validated(): void
    {
        // arrange
        $invoice = $this->createInvoice();
        $invoice->toPdf();

        // act
        $invoice->addItem(['unit_price' => 5]);

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Item at index 1 must have a title');

        $invoice->toPdf();
    }

    #[Test]
    public function every_package_exception_can_be_caught_at_once(): void
    {
        // arrange
        $invoice = Invoice::make();

        // act
        try {
            $invoice->toPdf();
            $this->fail('The invalid invoice was rendered');
        } catch (InvoicesException $e) {
            // assert
            $this->assertInstanceOf(\LogicException::class, $e);
        }
    }
}
