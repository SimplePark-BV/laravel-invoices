<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Services\FontResolver;
use Tests\TestCase;

final class FontResolverTest extends TestCase
{
    #[Test]
    public function no_custom_font_file_is_configured_by_default(): void
    {
        // act & assert
        $this->assertNull(FontResolver::customFontFile());
        $this->assertSame('Poppins', FontResolver::family(null));
    }

    #[Test]
    #[DataProvider('unresolvableFontFiles')]
    public function an_unreadable_font_file_falls_back_to_poppins_with_a_warning(string $fontFile): void
    {
        // arrange
        Config::set('invoices.pdf.font', 'Custom Font');
        Config::set('invoices.pdf.font_file', $fontFile);

        // assert
        Log::shouldReceive('warning')->once();

        // act
        $resolved = FontResolver::customFontFile();

        // assert
        $this->assertNull($resolved);
        $this->assertSame('Poppins', FontResolver::family($resolved));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unresolvableFontFiles(): array
    {
        return [
            'missing absolute file' => ['/non/existent/font.ttf'],
            'missing relative file' => ['non/existent/font.ttf'],
            'directory' => [__DIR__],
        ];
    }

    #[Test]
    public function an_absolute_font_file_is_used_as_it_is(): void
    {
        // arrange
        $fontFile = (string) realpath(__DIR__.'/../../../resources/fonts/Poppins-Regular.ttf');
        Config::set('invoices.pdf.font_file', $fontFile);

        // act & assert
        $this->assertSame($fontFile, FontResolver::customFontFile());
    }

    #[Test]
    public function a_relative_font_file_is_read_from_the_base_path(): void
    {
        // arrange
        $packageRoot = (string) realpath(__DIR__.'/../../..');
        $this->app?->setBasePath($packageRoot);
        Config::set('invoices.pdf.font_file', 'resources/../resources/fonts/Poppins-Regular.ttf');

        // act & assert
        $this->assertSame($packageRoot.'/resources/fonts/Poppins-Regular.ttf', FontResolver::customFontFile());
    }

    #[Test]
    public function the_family_name_keeps_only_safe_characters(): void
    {
        // arrange
        Config::set('invoices.pdf.font', "DejaVu Sans'; } body { color: red");

        // act & assert
        $this->assertSame('DejaVu Sans  body  color red', FontResolver::family(null));
    }

    #[Test]
    public function the_bundled_font_has_four_faces(): void
    {
        // act
        $css = FontResolver::fontFaces(null);

        // assert
        $this->assertSame(4, substr_count($css, "font-family: 'Poppins'"));
        $this->assertStringContainsString('Poppins-Regular.ttf', $css);
        $this->assertStringContainsString('Poppins-MediumItalic.ttf', $css);
    }

    #[Test]
    public function a_custom_font_file_is_registered_for_every_weight_and_style(): void
    {
        // arrange
        $fontFile = (string) realpath(__DIR__.'/../../../resources/fonts/Poppins-Regular.ttf');
        Config::set('invoices.pdf.font', 'Custom Font');
        Config::set('invoices.pdf.font_file', $fontFile);

        // act
        $css = FontResolver::fontFaces($fontFile);

        // assert
        $this->assertSame(4, substr_count($css, "font-family: 'Custom Font'; src: url('file://{$fontFile}')"));
        $this->assertStringNotContainsString("'Poppins'", $css);
    }

    #[Test]
    public function a_font_dompdf_knows_by_name_needs_no_font_faces(): void
    {
        // arrange
        Config::set('invoices.pdf.font', 'DejaVu Sans');

        // act & assert
        $this->assertSame('DejaVu Sans', FontResolver::family(null));
        $this->assertSame('', FontResolver::fontFaces(null));
    }
}
