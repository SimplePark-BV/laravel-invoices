<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Support;

/**
 * Reads typed values from the invoices config, so values from env() strings never cause type errors.
 *
 * @internal
 */
final class Settings
{
    /**
     * Get a non-empty string from the config, or the default.
     */
    public static function string(string $key, string $default): string
    {
        $value = config($key);

        return is_string($value) && trim($value) !== '' ? $value : $default;
    }

    /**
     * Get a string from the config, or null when it is not set or empty.
     */
    public static function nullableString(string $key): ?string
    {
        $value = config($key);

        return is_string($value) ? Text::clean($value) : null;
    }

    /**
     * Get an integer from the config, or the default when it is not a number.
     */
    public static function int(string $key, int $default): int
    {
        $value = config($key);

        return is_numeric($value) ? (int) $value : $default;
    }
}
