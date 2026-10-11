<?php

namespace Tests\Feature\UsageReceipt;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\ExpectedTotalMismatchException;
use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;
use SimpleParkBv\Invoices\Models\UsageReceipt;
use Tests\TestCase;
use Tests\Traits\CreatesTestReceipts;

final class ValidationTest extends TestCase
{
    use CreatesTestReceipts;

    #[Test]
    #[DataProvider('outputs')]
    public function every_output_validates_the_receipt_first(string $output): void
    {
        // arrange
        $receipt = UsageReceipt::make(['buyer' => ['name' => 'Jan']]);

        // assert
        $this->expectException(InvalidUsageReceiptException::class);
        $this->expectExceptionMessage('A usage receipt must have at least one item');

        // act
        $receipt->{$output}();
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
        $receipt = $this->createReceipt(['expected_total' => 5.49]);

        // assert
        $this->expectException(ExpectedTotalMismatchException::class);

        // act
        $receipt->{$output}();
    }

    #[Test]
    public function a_session_without_duration_renders(): void
    {
        // arrange
        $receipt = $this->createReceipt(['items' => [$this->receiptItemData(['end_date' => '2026-10-02 10:00'])]]);

        // act
        $pdf = $receipt->toPdf();

        // assert
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    #[Test]
    public function a_buyer_without_a_name_renders(): void
    {
        // arrange
        $receipt = $this->createReceipt(['buyer' => ['name' => null, 'phone' => '0612345678']]);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertStringContainsString('0612345678', $html);
    }
}
