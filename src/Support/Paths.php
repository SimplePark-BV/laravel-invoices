<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Support;

/**
 * Resolves the file paths given in the invoices config and to documents.
 *
 * @internal
 */
final class Paths
{
    /**
     * Resolve a path: absolute paths are kept, relative paths are read from the app's base path,
     * so the result does not depend on the working directory.
     */
    public static function resolve(string $path): string
    {
        return self::isAbsolute($path) ? $path : base_path($path);
    }

    /**
     * Check if the path is absolute on unix or windows.
     */
    public static function isAbsolute(string $path): bool
    {
        return preg_match('#^(?:/|\\\\|[A-Za-z]:[\\\\/])#', $path) === 1;
    }
}
