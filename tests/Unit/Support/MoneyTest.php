<?php

namespace Tests\Unit\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidDocumentException;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Support\Money;
use Tests\TestCase;

final class MoneyTest extends TestCase
{
    #[Test]
    #[DataProvider('amountsInCents')]
    public function to_cents_converts_amounts(float|int|string $amount, int $expected): void
    {
        // act
        $cents = Money::toCents($amount, 'amount');

        // assert
        $this->assertSame($expected, $cents);
    }

    /**
     * @return array<string, array{float|int|string, int}>
     */
    public static function amountsInCents(): array
    {
        return [
            'integer' => [12, 1200],
            'float with two decimals' => [12.34, 1234],
            'float that is not exact in binary' => [0.29, 29],
            'negative' => [-1.05, -105],
            'zero' => [0, 0],
            'negative zero' => [-0.0, 0],
            'numeric string' => ['12.50', 1250],
            'numeric string with spaces' => [' 7.10 ', 710],
            'large amount' => [123456789.99, 12345678999],
            'very large float' => [160000000.02, 16000000002],
        ];
    }

    #[Test]
    public function to_cents_keeps_null(): void
    {
        // act & assert
        $this->assertNull(Money::toCents(null, 'amount'));
    }

    #[Test]
    #[DataProvider('invalidAmounts')]
    public function to_cents_rejects_invalid_amounts(float|string $amount, string $message): void
    {
        // assert
        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage($message);

        // act
        Money::toCents($amount, 'amount');
    }

    /**
     * @return array<string, array{float|string, string}>
     */
    public static function invalidAmounts(): array
    {
        return [
            'three decimals' => [12.345, 'at most 2 decimals'],
            'sub cent' => [0.005, 'at most 2 decimals'],
            'comma as decimal separator' => ['12,50', 'must be a number'],
            'thousands separator' => ['1.234,56', 'must be a number'],
            'empty string' => ['', 'must be a number'],
            'text' => ['abc', 'must be a number'],
            'not a number' => [NAN, 'finite number'],
            'infinite' => [INF, 'finite number'],
            'too large' => [1e12, 'too large'],
        ];
    }

    #[Test]
    public function to_cents_throws_the_given_exception(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);

        // act
        Money::toCents('abc', 'unit price', InvalidInvoiceException::class);
    }

    #[Test]
    #[DataProvider('multiplications')]
    public function multiply_rounds_half_away_from_zero_to_whole_cents(int $cents, float|int $quantity, int $expected): void
    {
        // act & assert
        $this->assertSame($expected, Money::multiply($cents, $quantity));
    }

    #[Test]
    public function multiply_throws_when_the_result_is_too_large(): void
    {
        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('The line total is too large');

        // act: 1e8 cents times 1e11 is more than an integer holds
        Money::multiply(100_000_000, 1e11, 'line total', InvalidInvoiceException::class);
    }

    /**
     * @return array<string, array{int, float|int, int}>
     */
    public static function multiplications(): array
    {
        return [
            'integer quantity' => [1210, 2, 2420],
            'fractional quantity' => [1479, 2.5, 3698],
            'half a cent rounds up' => [33, 1.5, 50],
            'negative half a cent rounds down' => [-33, 1.5, -50],
            'float noise does not flip the rounding' => [100, 1.005, 101],
            'below half a cent rounds down' => [1, 0.4, 0],
        ];
    }
}
