<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Services;

use Illuminate\Support\Facades\Log;
use SimpleParkBv\Invoices\Support\Settings;

/**
 * Resolves the font of the PDFs: the bundled Poppins, a font dompdf knows by name, or a custom font file.
 *
 * @internal
 */
final class FontResolver
{
    private const BUNDLED_FAMILY = 'Poppins';

    /**
     * The bundled faces: weight, style and file. Medium is used for bold text.
     *
     * @var list<array{int, string, string}>
     */
    private const BUNDLED_FACES = [
        [400, 'normal', 'Poppins-Regular.ttf'],
        [400, 'italic', 'Poppins-Italic.ttf'],
        [700, 'normal', 'Poppins-Medium.ttf'],
        [700, 'italic', 'Poppins-MediumItalic.ttf'],
    ];

    /**
     * Get the absolute path of the configured font file, or null when none is configured or it cannot be read.
     *
     * Relative paths are read from the app's base path, so the result does not depend on the working directory.
     */
    public static function customFontFile(): ?string
    {
        $fontFile = Settings::nullableString('invoices.pdf.font_file');

        if ($fontFile === null) {
            return null;
        }

        $path = realpath(self::isAbsolutePath($fontFile) ? $fontFile : base_path($fontFile));

        if ($path === false || ! is_file($path) || ! is_readable($path)) {
            Log::warning("Invoices: the font file '{$fontFile}' cannot be read, the PDF uses the bundled Poppins font instead");

            return null;
        }

        return $path;
    }

    /**
     * Get the font family the documents use.
     */
    public static function family(?string $customFontFile): string
    {
        $font = trim((string) preg_replace('/[^A-Za-z0-9 _-]/', '', Settings::string('invoices.pdf.font', self::BUNDLED_FAMILY)));

        // a configured font file that cannot be read falls back to the bundled font
        if ($font === '' || (Settings::nullableString('invoices.pdf.font_file') !== null && $customFontFile === null)) {
            return self::BUNDLED_FAMILY;
        }

        return $font;
    }

    /**
     * Get the @font-face rules for the font family.
     *
     * A custom font file is registered for every weight and style, because dompdf only matches exact faces.
     */
    public static function fontFaces(?string $customFontFile): string
    {
        $family = self::family($customFontFile);

        if ($customFontFile !== null) {
            $faces = array_map(
                static fn (array $face): array => [$face[0], $face[1], $customFontFile],
                self::BUNDLED_FACES,
            );
        } elseif ($family === self::BUNDLED_FAMILY) {
            $faces = array_map(
                static fn (array $face): array => [$face[0], $face[1], self::fontsPath().'/'.$face[2]],
                self::BUNDLED_FACES,
            );
        } else {
            // a font dompdf knows by name, such as DejaVu Sans
            return '';
        }

        $css = '';

        foreach ($faces as [$weight, $style, $path]) {
            $url = 'file://'.str_replace(['\\', "'"], ['/', "\\'"], $path);
            $css .= "@font-face { font-family: '{$family}'; src: url('{$url}') format('truetype'); font-weight: {$weight}; font-style: {$style}; }\n";
        }

        return $css;
    }

    private static function fontsPath(): string
    {
        return dirname(__DIR__, 2).'/resources/fonts';
    }

    /**
     * Check if the path is absolute on unix or windows.
     */
    private static function isAbsolutePath(string $path): bool
    {
        return preg_match('#^(?:/|\\\\|[A-Za-z]:[\\\\/])#', $path) === 1;
    }
}
