<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;
use SimpleParkBv\Invoices\Models\UsageReceiptLine;
use Tests\TestCase;
use Tests\Traits\CreatesTestReceipts;

final class UsageReceiptLineTest extends TestCase
{
    use CreatesTestReceipts;

    #[Test]
    public function make_fills_the_line_from_an_array(): void
    {
        // act
        $line = $this->createReceiptLine(['amount' => -2.00]);

        // assert
        $this->assertSame('Service fee', $line->getLabel());
        $this->assertSame(-2.0, $line->getAmount());
        $this->assertSame(-200, $line->getAmountCents());
        $this->assertSame(-200, $line->getTotalCents());
        $this->assertSame(21.0, $line->getTaxPercentage());
    }

    #[Test]
    public function make_rejects_unknown_keys(): void
    {
        // assert
        $this->expectException(InvalidUsageReceiptException::class);
        $this->expectExceptionMessage("Unknown key 'tax' for UsageReceiptLine");

        // act
        UsageReceiptLine::make(['label' => 'Service fee', 'amount' => 0.35, 'tax' => 21]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Test]
    #[DataProvider('invalidLines')]
    public function validate_rejects_invalid_lines(array $data, string $message): void
    {
        // arrange
        $line = UsageReceiptLine::make($data);

        // assert
        $this->expectException(InvalidUsageReceiptException::class);
        $this->expectExceptionMessage($message);

        // act
        $line->validate(2);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidLines(): array
    {
        return [
            'no label' => [['amount' => 1], 'Line at index 2 must have a label'],
            'blank label' => [['label' => ' ', 'amount' => 1], 'Line at index 2 must have a label'],
            'no amount' => [['label' => 'Fee'], 'Line at index 2 must have an amount'],
            'tax above 100' => [['label' => 'Fee', 'amount' => 1, 'tax_percentage' => 101], 'Line at index 2 must have a tax percentage between 0 and 100, or null'],
        ];
    }

    #[Test]
    public function to_array_returns_the_fields(): void
    {
        // act & assert
        $this->assertSame(
            ['label' => 'Service fee', 'amount' => 0.35, 'tax_percentage' => 21.0],
            $this->createReceiptLine()->toArray(),
        );
    }
}
