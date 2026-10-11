<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Services\FontResolver;
use Tests\TestCase;

final class FontResolverTest extends TestCase
{
    #[Test]
    #[DataProvider('unresolvable_font_file_data_provider')]
    public function get_font_file_returns_null_when_not_resolvable(mixed $fontFile): void
    {
        // arrange
        Config::set('invoices.pdf.font_file', $fontFile);

        // act
        $resolved = FontResolver::getFontFile();

        // assert
        $this->assertNull($resolved);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function unresolvable_font_file_data_provider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'non string' => [123],
            'missing absolute file' => ['/non/existent/font.ttf'],
            'missing relative file' => ['non/existent/font.ttf'],
            'directory' => [__DIR__],
        ];
    }

    #[Test]
    public function get_font_file_returns_absolute_path_unchanged(): void
    {
        // arrange
        $fontFile = realpath(__DIR__.'/../../../resources/fonts/Poppins-Regular.ttf');
        Config::set('invoices.pdf.font_file', $fontFile);

        // act
        $resolved = FontResolver::getFontFile();

        // assert
        $this->assertSame($fontFile, $resolved);
    }

    #[Test]
    public function get_font_file_resolves_relative_path_from_base_path(): void
    {
        // arrange
        $packageRoot = (string) realpath(__DIR__.'/../../..');
        $this->app?->setBasePath($packageRoot);
        Config::set('invoices.pdf.font_file', 'resources/fonts/Poppins-Regular.ttf');

        // act
        $resolved = FontResolver::getFontFile();

        // assert
        $this->assertSame($packageRoot.'/resources/fonts/Poppins-Regular.ttf', $resolved);
    }

    #[Test]
    public function get_font_file_normalizes_path(): void
    {
        // arrange
        $packageRoot = (string) realpath(__DIR__.'/../../..');
        Config::set('invoices.pdf.font_file', $packageRoot.'/resources/../resources/fonts/Poppins-Regular.ttf');

        // act
        $resolved = FontResolver::getFontFile();

        // assert
        $this->assertSame($packageRoot.'/resources/fonts/Poppins-Regular.ttf', $resolved);
    }
}
