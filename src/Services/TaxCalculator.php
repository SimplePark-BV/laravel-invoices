<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Services;

use SimpleParkBv\Invoices\Models\TaxGroup;
use SimpleParkBv\Invoices\Models\Totals;

/**
 * Calculates the totals of documents whose amounts include tax.
 *
 * The tax of a rate is calculated once over the sum of all amounts with that rate, and rounded half
 * away from zero to whole cents. All arithmetic is done in integer cents, so there is no float drift.
 */
final class TaxCalculator
{
    /**
     * Calculate the totals of the given lines.
     *
     * @param  iterable<\SimpleParkBv\Invoices\Contracts\TaxableInterface>  $lines
     */
    public static function calculate(iterable $lines): Totals
    {
        $totalCents = 0;

        /** @var array<int, int> $grossCentsPerRate gross cents, keyed by tax rate in basis points */
        $grossCentsPerRate = [];

        foreach ($lines as $line) {
            $cents = $line->getTotalCents();
            $totalCents += $cents;

            $percentage = $line->getTaxPercentage();

            if ($percentage === null) {
                continue;
            }

            $basisPoints = self::toBasisPoints($percentage);
            $grossCentsPerRate[$basisPoints] = ($grossCentsPerRate[$basisPoints] ?? 0) + $cents;
        }

        krsort($grossCentsPerRate);

        $groups = [];

        foreach ($grossCentsPerRate as $basisPoints => $grossCents) {
            $groups[] = new TaxGroup($basisPoints / 100, $grossCents, self::includedTax($grossCents, $basisPoints));
        }

        return new Totals($totalCents, $groups);
    }

    /**
     * Calculate the tax included in an amount, in cents, rounded half away from zero.
     *
     * tax = gross * rate / (1 + rate), with the rate in basis points (21% is 2100).
     */
    public static function includedTax(int $grossCents, int $basisPoints): int
    {
        $divisor = 10_000 + $basisPoints;

        if ($grossCents === 0 || $basisPoints <= 0 || $divisor <= 0) {
            return 0;
        }

        $absoluteCents = abs($grossCents);

        // round(x / d) half up is floor((2x + d) / 2d), done in integers to avoid float noise, unless
        // the amount is so large that the integers would overflow
        $tax = $absoluteCents <= intdiv(PHP_INT_MAX - 2 * $divisor, 2 * $basisPoints)
            ? intdiv(2 * $absoluteCents * $basisPoints + $divisor, 2 * $divisor)
            : (int) round($absoluteCents * $basisPoints / $divisor);

        return $grossCents < 0 ? -$tax : $tax;
    }

    /**
     * Convert a tax percentage to basis points, e.g. 21.0 to 2100 and 9.5 to 950.
     */
    public static function toBasisPoints(float $percentage): int
    {
        return (int) round($percentage * 100);
    }
}
