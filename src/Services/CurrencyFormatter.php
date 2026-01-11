<?php

namespace SimpleParkBv\Invoices\Services;

/**
 * Service class for formatting currency values.
 */
final class CurrencyFormatter
{
    /**
     * Format a currency amount with symbol and proper decimal formatting.
     */
    public static function format(float $amount, ?string $currencySymbol = null, ?string $decimalSeparator = null, ?string $thousandsSeparator = null): string
    {
        $currencySymbol = $currencySymbol ?? config('invoices.currency_symbol', '€');
        $decimalSeparator = $decimalSeparator ?? config('invoices.decimal_separator', ',');
        $thousandsSeparator = $thousandsSeparator ?? config('invoices.thousands_separator', '.');

        return $currencySymbol.' '.number_format($amount, 2, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Get the currency symbol from config.
     */
    public static function getSymbol(): string
    {
        return config('invoices.currency_symbol', '€');
    }

    /**
     * Get the decimal separator.
     */
    public static function getDecimalSeparator(): string
    {
        return config('invoices.decimal_separator', ',');
    }

    /**
     * Get the thousands separator.
     */
    public static function getThousandsSeparator(): string
    {
        return config('invoices.thousands_separator', '.');
    }
}
