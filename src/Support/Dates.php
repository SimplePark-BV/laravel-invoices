<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Support;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use SimpleParkBv\Invoices\Exceptions\InvalidDocumentException;
use Throwable;

/**
 * Parses the dates given to documents into immutable instances.
 *
 * @internal
 */
final class Dates
{
    /**
     * Parse a date, keeping the moment in time of date objects and reading strings in the display timezone.
     *
     * @param  class-string<\SimpleParkBv\Invoices\Exceptions\InvalidDocumentException>  $exception
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the string is empty or not a valid date
     */
    public static function parse(DateTimeInterface|string|null $value, string $field, string $exception = InvalidDocumentException::class): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (trim($value) === '') {
            throw new $exception("The {$field} must be a date, an empty string given");
        }

        // created outside the try, so an invalid timezone config is reported as such and not as an invalid date
        $timezone = new DateTimeZone(self::displayTimezone());

        try {
            // strings without a timezone are read in the display timezone, so the date given is the date shown
            $date = new DateTimeImmutable($value, $timezone);
        } catch (Throwable $e) {
            throw new $exception("The {$field} must be a valid date, '{$value}' given", 0, $e);
        }

        // php accepts dates such as 2026-02-30 by rolling them over, but reports a warning
        $errors = DateTimeImmutable::getLastErrors();

        if ($errors !== false && $errors['warning_count'] > 0) {
            throw new $exception("The {$field} must be a valid date, '{$value}' given");
        }

        return CarbonImmutable::instance($date);
    }

    /**
     * The timezone dates are shown in: the configured invoices timezone, or else the app timezone.
     */
    public static function displayTimezone(): string
    {
        foreach ([config('invoices.timezone'), config('app.timezone')] as $timezone) {
            if (is_string($timezone) && $timezone !== '') {
                return $timezone;
            }
        }

        return 'UTC';
    }
}
