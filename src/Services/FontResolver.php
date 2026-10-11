<?php

namespace SimpleParkBv\Invoices\Services;

/**
 * Service class for resolving the configured pdf font.
 */
final class FontResolver
{
    /**
     * Get the absolute path to the configured custom font file.
     *
     * relative paths are resolved from the application base path, so the result
     * does not depend on the current working directory (web vs. console).
     * returns null when no font file is configured or the file is not readable.
     */
    public static function getFontFile(): ?string
    {
        $fontFile = config('invoices.pdf.font_file');

        if (! is_string($fontFile) || $fontFile === '') {
            return null;
        }

        if (! self::isAbsolutePath($fontFile)) {
            $fontFile = base_path($fontFile);
        }

        $resolved = realpath($fontFile);

        if ($resolved === false || ! is_file($resolved) || ! is_readable($resolved)) {
            return null;
        }

        return $resolved;
    }

    /**
     * Check if the path is absolute on unix or windows.
     */
    private static function isAbsolutePath(string $path): bool
    {
        return preg_match('#^(?:/|\\\\|[A-Za-z]:[\\\\/])#', $path) === 1;
    }
}
