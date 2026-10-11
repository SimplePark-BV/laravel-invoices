<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Locale;
use SimpleParkBv\Invoices\Support\Dates;

/**
 * Formats amounts, percentages, quantities and dates for one document language.
 *
 * The separators come from the invoices::format translations of the language, so every
 * language, including one added by publishing the translations, formats its own numbers.
 */
final class Formatter
{
    private ?string $decimalSeparator = null;

    private ?string $thousandsSeparator = null;

    public function __construct(
        private readonly string $language,
    ) {
        //
    }

    /**
     * Format an amount in cents with the currency symbol, e.g. "€ 1.234,50".
     *
     * The symbol is joined by a non-breaking space, so it never wraps away from the amount.
     */
    public function money(int $cents): string
    {
        $amount = number_format($cents / 100, 2, $this->decimalSeparator(), $this->thousandsSeparator());
        $symbol = config('invoices.currency_symbol', '€');

        return is_string($symbol) && $symbol !== '' ? $symbol."\u{00A0}".$amount : $amount;
    }

    /**
     * Format a percentage without trailing zeros, e.g. "21%" or "9,5%".
     */
    public function percentage(float $percentage): string
    {
        return $this->number($percentage, 2).'%';
    }

    /**
     * Format a quantity without trailing zeros, e.g. "2" or "1,5".
     */
    public function quantity(float|int $quantity): string
    {
        return $this->number($quantity, 4);
    }

    /**
     * Format a number with at most the given number of decimals, without trailing zeros.
     */
    public function number(float|int $number, int $maxDecimals): string
    {
        $decimalSeparator = $this->decimalSeparator();
        $formatted = number_format((float) $number, $maxDecimals, $decimalSeparator, $this->thousandsSeparator());

        if ($maxDecimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), $decimalSeparator);
        }

        // number_format() keeps the sign of values that round to zero, e.g. "-0"
        return $formatted === '-0' ? '0' : $formatted;
    }

    /**
     * Format a date in the display timezone, with month and day names in the document language.
     */
    public function date(?DateTimeInterface $date, string $format): string
    {
        if ($date === null) {
            return '';
        }

        return CarbonImmutable::instance($date)
            ->setTimezone(Dates::displayTimezone())
            ->settings(['locale' => $this->language])
            ->translatedFormat($format);
    }

    /**
     * Format a country: a two-letter ISO code becomes its name in the document language, e.g. "NL" becomes
     * "Nederland" or "Netherlands". Any other text is kept as it is, and so is a code when the intl
     * extension is missing or does not know it.
     */
    public function country(?string $country): ?string
    {
        if ($country === null || preg_match('/^[A-Za-z]{2}$/', $country) !== 1 || ! class_exists(Locale::class)) {
            return $country;
        }

        $code = strtoupper($country);
        $name = Locale::getDisplayRegion("und-{$code}", $this->language);

        // intl answers with the code itself, or an unknown region, when it has no name for it
        return $name === false || $name === '' || $name === $code || $name === 'Unknown Region' ? $code : $name;
    }

    private function decimalSeparator(): string
    {
        return $this->decimalSeparator ??= $this->separator('decimal_separator', ',');
    }

    private function thousandsSeparator(): string
    {
        return $this->thousandsSeparator ??= $this->separator('thousands_separator', '.');
    }

    private function separator(string $key, string $default): string
    {
        // without fallback, so a language without format translations gets the defaults, not the separators of the fallback locale
        $value = app('translator')->get("invoices::format.{$key}", [], $this->language, false);

        return is_string($value) && $value !== "invoices::format.{$key}" ? $value : $default;
    }
}
