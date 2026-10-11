<?php

namespace Tests\Unit\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Carbon as IlluminateCarbon;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidDocumentException;
use SimpleParkBv\Invoices\Support\Dates;
use Tests\TestCase;

final class DatesTest extends TestCase
{
    #[Test]
    #[DataProvider('dateObjects')]
    public function parse_keeps_the_moment_in_time_of_date_objects(\DateTimeInterface $date): void
    {
        // act
        $parsed = Dates::parse($date, 'date');

        // assert
        $this->assertInstanceOf(CarbonImmutable::class, $parsed);
        $this->assertSame('2026-07-01T08:00:00+00:00', $parsed->utc()->toIso8601String());
    }

    /**
     * @return array<string, array{\DateTimeInterface}>
     */
    public static function dateObjects(): array
    {
        $utc = new DateTimeZone('UTC');

        return [
            'carbon immutable' => [CarbonImmutable::parse('2026-07-01 08:00', 'UTC')],
            'carbon' => [Carbon::parse('2026-07-01 08:00', 'UTC')],
            'illuminate carbon' => [IlluminateCarbon::parse('2026-07-01 10:00', 'Europe/Amsterdam')],
            'date time' => [new DateTime('2026-07-01 08:00', $utc)],
            'date time immutable' => [new DateTimeImmutable('2026-07-01 08:00', $utc)],
        ];
    }

    #[Test]
    public function parse_reads_strings_in_the_app_timezone(): void
    {
        // act
        $parsed = Dates::parse('2026-07-01 10:00', 'date');

        // assert
        $this->assertSame('2026-07-01T10:00:00+02:00', $parsed?->toIso8601String());
    }

    #[Test]
    public function parse_reads_strings_in_the_display_timezone(): void
    {
        // arrange
        Config::set('invoices.timezone', 'America/New_York');

        // act
        $parsed = Dates::parse('2026-10-11', 'date');

        // assert: the date given is the date shown
        $this->assertSame('2026-10-11T00:00:00-04:00', $parsed?->toIso8601String());
    }

    #[Test]
    public function parse_does_not_share_the_given_instance(): void
    {
        // arrange
        $date = Carbon::parse('2026-07-01 10:00');

        // act
        $parsed = Dates::parse($date, 'date');
        $date->addDay();

        // assert
        $this->assertSame('2026-07-01', $parsed?->toDateString());
    }

    #[Test]
    public function parse_keeps_null(): void
    {
        // act & assert
        $this->assertNull(Dates::parse(null, 'date'));
    }

    #[Test]
    #[DataProvider('invalidDates')]
    public function parse_rejects_invalid_dates(string $date): void
    {
        // assert
        $this->expectException(InvalidDocumentException::class);

        // act
        Dates::parse($date, 'date');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidDates(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
            'text' => ['not a date'],
            'day that does not exist' => ['2026-02-30'],
        ];
    }

    #[Test]
    public function display_timezone_is_the_app_timezone_by_default(): void
    {
        // act & assert
        $this->assertSame('Europe/Amsterdam', Dates::displayTimezone());
    }

    #[Test]
    public function display_timezone_can_be_configured(): void
    {
        // arrange
        Config::set('invoices.timezone', 'America/New_York');

        // act & assert
        $this->assertSame('America/New_York', Dates::displayTimezone());
    }
}
