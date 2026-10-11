<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Support;

use SimpleParkBv\Invoices\Exceptions\InvalidDocumentException;

/**
 * Converts amounts of money to integer cents, so totals are computed without float drift.
 *
 * @internal
 */
final class Money
{
    /**
     * The largest amount accepted, in euros, so cent calculations stay within integer range.
     */
    private const MAX_AMOUNT = 10_000_000_000;

    /**
     * Convert an amount in euros to cents.
     *
     * @param  class-string<\SimpleParkBv\Invoices\Exceptions\InvalidDocumentException>  $exception
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the amount is not a number with at most 2 decimals
     */
    public static function toCents(float|int|string|null $amount, string $field, string $exception = InvalidDocumentException::class): ?int
    {
        if ($amount === null) {
            return null;
        }

        $number = self::toNumber($amount, $field, $exception);

        if (abs($number) >= self::MAX_AMOUNT) {
            throw new $exception("The {$field} is too large, {$number} given");
        }

        $cents = $number * 100;
        $rounded = round($cents);

        // a binary float is never exactly 12.34, so allow for float noise, which grows with the amount, when checking the decimals
        if (abs($cents - $rounded) > max(1e-6, abs($cents) * 4 * PHP_FLOAT_EPSILON)) {
            throw new $exception("The {$field} must have at most 2 decimals, {$number} given");
        }

        return (int) $rounded;
    }

    /**
     * Convert a number, or a numeric string with a dot as decimal separator, to a finite float.
     *
     * @param  class-string<\SimpleParkBv\Invoices\Exceptions\InvalidDocumentException>  $exception
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the value is not a finite number
     */
    public static function toNumber(float|int|string $value, string $field, string $exception = InvalidDocumentException::class): float
    {
        if (is_string($value)) {
            if (! is_numeric(trim($value))) {
                throw new $exception("The {$field} must be a number, '{$value}' given");
            }

            $value = trim($value);
        }

        $value = (float) $value;

        if (! is_finite($value)) {
            throw new $exception("The {$field} must be a finite number");
        }

        // turn -0.0 into 0.0, so it is never shown as "-0"
        return $value + 0.0;
    }

    /**
     * Multiply an amount in cents by a quantity, rounding half away from zero to whole cents.
     *
     * @param  class-string<\SimpleParkBv\Invoices\Exceptions\InvalidDocumentException>  $exception
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the result is too large
     */
    public static function multiply(int $cents, float|int $quantity, string $field = 'line total', string $exception = InvalidDocumentException::class): int
    {
        // round to 6 decimals first, so float noise (100 * 1.005 = 100.4999...) does not flip the rounding
        $result = round(round($cents * $quantity, 6));

        // the same limit as for amounts, so the cast below never overflows
        if (! is_finite($result) || abs($result) >= self::MAX_AMOUNT * 100) {
            throw new $exception("The {$field} is too large");
        }

        return (int) $result;
    }

    /**
     * Convert cents to an amount in euros.
     */
    public static function toFloat(int $cents): float
    {
        return $cents / 100;
    }
}
