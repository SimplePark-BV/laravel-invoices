<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Services\CurrencyFormatter;
use Tests\TestCase;

final class CurrencyFormatterTest extends TestCase
{
    #[Test]
    public function format_uses_default_config_values(): void
    {
        // arrange
        Config::set('invoices.currency_symbol', '€');
        Config::set('invoices.decimal_separator', ',');
        Config::set('invoices.thousands_separator', '.');

        // act
        $formatted = CurrencyFormatter::format(1234.56);

        // assert
        $this->assertEquals('€ 1.234,56', $formatted);
    }

    #[Test]
    #[DataProvider('format_custom_values_data_provider')]
    public function format_uses_custom_parameters(float $amount, ?string $currencySymbol, ?string $decimalSeparator, ?string $thousandsSeparator, string $expected): void
    {
        // arrange
        Config::set('invoices.currency_symbol', '€');
        Config::set('invoices.decimal_separator', ',');
        Config::set('invoices.thousands_separator', '.');

        // act
        $formatted = CurrencyFormatter::format($amount, $currencySymbol, $decimalSeparator, $thousandsSeparator);

        // assert
        $this->assertEquals($expected, $formatted);
    }

    /**
     * @return array<string, array{0: float, 1: string|null, 2: string|null, 3: string|null, 4: string}>
     */
    public static function format_custom_values_data_provider(): array
    {
        return [
            'USD with dot decimal' => [1234.56, '$', '.', ',', '$ 1,234.56'],
            'GBP with space thousands' => [1234.56, '£', '.', ' ', '£ 1 234.56'],
            'EUR with comma decimal' => [1234.56, '€', ',', '.', '€ 1.234,56'],
            'zero amount' => [0.00, '€', ',', '.', '€ 0,00'],
            'large amount' => [1234567.89, '€', ',', '.', '€ 1.234.567,89'],
            'small amount' => [0.01, '€', ',', '.', '€ 0,01'],
            'no thousands separator' => [1234.56, '€', ',', '', '€ 1234,56'],
            'null parameters use config' => [1234.56, null, null, null, '€ 1.234,56'],
        ];
    }

    #[Test]
    public function format_uses_config_when_parameters_null(): void
    {
        // arrange
        Config::set('invoices.currency_symbol', '$');
        Config::set('invoices.decimal_separator', '.');
        Config::set('invoices.thousands_separator', ',');

        // act
        $formatted = CurrencyFormatter::format(1234.56, null, null, null);

        // assert
        $this->assertEquals('$ 1,234.56', $formatted);
    }

    #[Test]
    public function get_symbol_returns_string(): void
    {
        // arrange & act
        // test that the method returns a valid string
        // (either from config file default '€' or from method default)
        $symbol = CurrencyFormatter::getSymbol();

        // assert
        // should return a string (from config file or default parameter)
        $this->assertIsString($symbol); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertNotEmpty($symbol);
    }

    #[Test]
    public function get_symbol_returns_from_config(): void
    {
        // arrange
        Config::set('invoices.currency_symbol', '$');

        // act
        $symbol = CurrencyFormatter::getSymbol();

        // assert
        $this->assertEquals('$', $symbol);
    }

    #[Test]
    public function get_decimal_separator_uses_config_default(): void
    {
        // arrange & act
        // test that the method works with config() default parameter
        $separator = CurrencyFormatter::getDecimalSeparator();

        // assert
        // should return a string (either from config or default ',')
        $this->assertIsString($separator); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertNotEmpty($separator);
    }

    #[Test]
    public function get_decimal_separator_returns_from_config(): void
    {
        // arrange
        Config::set('invoices.decimal_separator', '.');

        // act
        $separator = CurrencyFormatter::getDecimalSeparator();

        // assert
        $this->assertEquals('.', $separator);
    }

    #[Test]
    public function get_thousands_separator_uses_config_default(): void
    {
        // arrange & act
        // test that the method works with config() default parameter
        $separator = CurrencyFormatter::getThousandsSeparator();

        // assert
        // should return a string (either from config or default '.')
        $this->assertIsString($separator); // @phpstan-ignore-line method.alreadyNarrowedType
        $this->assertNotEmpty($separator);
    }

    #[Test]
    public function get_thousands_separator_returns_from_config(): void
    {
        // arrange
        Config::set('invoices.thousands_separator', ',');

        // act
        $separator = CurrencyFormatter::getThousandsSeparator();

        // assert
        $this->assertEquals(',', $separator);
    }

    #[Test]
    #[DataProvider('format_edge_cases_data_provider')]
    public function format_handles_edge_cases(float $amount, string $expected): void
    {
        // arrange
        Config::set('invoices.currency_symbol', '€');
        Config::set('invoices.decimal_separator', ',');
        Config::set('invoices.thousands_separator', '.');

        // act
        $formatted = CurrencyFormatter::format($amount);

        // assert
        $this->assertEquals($expected, $formatted);
    }

    /**
     * @return array<string, array{0: float, 1: string}>
     */
    public static function format_edge_cases_data_provider(): array
    {
        return [
            'very large number' => [999999999.99, '€ 999.999.999,99'],
            'very small number' => [0.01, '€ 0,01'],
            'exactly zero' => [0.00, '€ 0,00'],
            'negative number' => [-1234.56, '€ -1.234,56'],
            'precision rounding' => [10.999, '€ 11,00'],
            'precision rounding down' => [10.001, '€ 10,00'],
        ];
    }
}
