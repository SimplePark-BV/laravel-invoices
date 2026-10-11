<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Services;

use Illuminate\Support\Facades\Log;
use SimpleParkBv\Invoices\Support\Paths;

/**
 * Turns the logo of a document into a data URI, so dompdf never has to read files outside its chroot.
 *
 * @internal
 */
final class LogoResolver
{
    /**
     * @var array<string, string>
     */
    private const MIME_TYPES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];

    /**
     * Get the logo as a data URI, or null when there is no logo.
     *
     * A logo that cannot be used is left out and logged as a warning, so a missing image never stops a PDF.
     */
    public static function dataUri(?string $logo): ?string
    {
        if ($logo === null || trim($logo) === '') {
            return null;
        }

        if (str_starts_with($logo, 'data:')) {
            if (preg_match('#^data:image/(?:png|jpeg|gif|svg\+xml);base64,[A-Za-z0-9+/]+={0,2}$#', $logo) === 1) {
                return $logo;
            }

            return self::skip('the data URI is not a base64 encoded PNG, JPG, GIF or SVG image');
        }

        $path = Paths::resolve($logo);
        $mimeType = self::MIME_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;

        if ($mimeType === null) {
            return self::skip("'{$logo}' is not a PNG, JPG, GIF or SVG file");
        }

        if (! is_file($path) || ! is_readable($path)) {
            return self::skip("'{$logo}' cannot be read");
        }

        $contents = file_get_contents($path);
        $mimeType = $contents === false ? null : self::detectMimeType($contents, $mimeType);

        if ($contents === false || $mimeType === null) {
            return self::skip("'{$logo}' is not a valid image");
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    /**
     * Get the mime type of the image from its contents, or null when it is not a supported image.
     */
    private static function detectMimeType(string $contents, string $expectedMimeType): ?string
    {
        if ($expectedMimeType === 'image/svg+xml') {
            return str_contains($contents, '<svg') ? $expectedMimeType : null;
        }

        $info = @getimagesizefromstring($contents);

        return $info !== false && in_array($info['mime'], self::MIME_TYPES, true) ? $info['mime'] : null;
    }

    private static function skip(string $reason): null
    {
        Log::warning("Invoices: the logo is left out, because {$reason}");

        return null;
    }
}
