<?php

namespace Tests\Unit\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Services\Formatter;
use Tests\TestCase;

final class FormatterTest extends TestCase
{
    #[Test]
    #[DataProvider('amounts')]
    public function money_follows_the_language(string $language, int $cents, string $expected): void
    {
        // act & assert
        $this->assertSame($expected, (new Formatter($language))->money($cents));
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function amounts(): array
    {
        return [
            'dutch' => ['nl', 123450, "€\u{00A0}1.234,50"],
            'english' => ['en', 123450, "€\u{00A0}1,234.50"],
            'dutch negative' => ['nl', -500, "€\u{00A0}-5,00"],
            'zero' => ['nl', 0, "€\u{00A0}0,00"],
            'one cent' => ['en', 1, "€\u{00A0}0.01"],
            'millions' => ['nl', 123456789, "€\u{00A0}1.234.567,89"],
        ];
    }

    #[Test]
    public function money_uses_the_configured_symbol(): void
    {
        // arrange
        Config::set('invoices.currency_symbol', '$');

        // act & assert
        $this->assertSame("$\u{00A0}1.00", (new Formatter('en'))->money(100));
    }

    #[Test]
    public function money_without_symbol_shows_only_the_amount(): void
    {
        // arrange
        Config::set('invoices.currency_symbol', '');

        // act & assert
        $this->assertSame('1,00', (new Formatter('nl'))->money(100));
    }

    #[Test]
    #[DataProvider('percentages')]
    public function percentage_has_no_trailing_zeros(string $language, float $percentage, string $expected): void
    {
        // act & assert
        $this->assertSame($expected, (new Formatter($language))->percentage($percentage));
    }

    /**
     * @return array<string, array{string, float, string}>
     */
    public static function percentages(): array
    {
        return [
            'whole dutch' => ['nl', 21.0, '21%'],
            'fraction dutch' => ['nl', 9.5, '9,5%'],
            'fraction english' => ['en', 9.5, '9.5%'],
            'float noise' => ['nl', 0.21 * 100, '21%'],
            'zero' => ['nl', 0.0, '0%'],
            'negative zero' => ['nl', -0.0, '0%'],
        ];
    }

    #[Test]
    #[DataProvider('quantities')]
    public function quantity_has_no_trailing_zeros(string $language, float|int $quantity, string $expected): void
    {
        // act & assert
        $this->assertSame($expected, (new Formatter($language))->quantity($quantity));
    }

    /**
     * @return array<string, array{string, float|int, string}>
     */
    public static function quantities(): array
    {
        return [
            'integer' => ['nl', 2, '2'],
            'fraction dutch' => ['nl', 1.5, '1,5'],
            'fraction english' => ['en', 2.25, '2.25'],
            'thousands' => ['nl', 1000, '1.000'],
            'float noise' => ['nl', 0.1 + 0.2, '0,3'],
        ];
    }

    #[Test]
    public function date_is_shown_in_the_display_timezone(): void
    {
        // arrange
        $date = CarbonImmutable::parse('2026-01-31 23:30', 'UTC');

        // act & assert
        $this->assertSame('01-02-2026 00:30', (new Formatter('nl'))->date($date, 'd-m-Y H:i'));
    }

    #[Test]
    public function date_uses_month_names_of_the_language(): void
    {
        // arrange
        $date = CarbonImmutable::parse('2026-10-11');

        // act & assert
        $this->assertSame('11 oktober 2026', (new Formatter('nl'))->date($date, 'j F Y'));
        $this->assertSame('11 October 2026', (new Formatter('en'))->date($date, 'j F Y'));
    }

    #[Test]
    public function date_of_null_is_empty(): void
    {
        // act & assert
        $this->assertSame('', (new Formatter('nl'))->date(null, 'd-m-Y'));
    }

    #[Test]
    public function separators_of_a_language_without_format_translations_fall_back(): void
    {
        // act: "de" has no format translations, so the defaults are used, not the separators of the fallback locale (en)
        $formatted = (new Formatter('de'))->money(123450);

        // assert
        $this->assertSame("€\u{00A0}1.234,50", $formatted);
    }

    #[Test]
    #[RequiresPhpExtension('intl')]
    #[DataProvider('countries')]
    public function country_names_an_iso_code_in_the_language(string $language, ?string $country, ?string $expected): void
    {
        // act & assert
        $this->assertSame($expected, (new Formatter($language))->country($country));
    }

    /**
     * @return array<string, array{string, string|null, string|null}>
     */
    public static function countries(): array
    {
        return [
            'dutch' => ['nl', 'NL', 'Nederland'],
            'english' => ['en', 'NL', 'Netherlands'],
            'lowercase code' => ['nl', 'be', 'België'],
            'a name is kept' => ['en', 'Nederland', 'Nederland'],
            'an unknown code is kept' => ['en', 'ZZ', 'ZZ'],
            'none' => ['nl', null, null],
        ];
    }
}
