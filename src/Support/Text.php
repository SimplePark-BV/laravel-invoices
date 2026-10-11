<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Support;

/**
 * Normalises the texts given to documents.
 *
 * @internal
 */
final class Text
{
    /**
     * Trim a text, and turn an empty text into null.
     */
    public static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Make a text safe to use as a file name, by replacing the characters file systems and headers reject.
     */
    public static function filename(string $value): string
    {
        return (string) preg_replace('/[\\\\\/:*?"<>|\x00-\x1F\x7F]+/', '-', $value);
    }
}
