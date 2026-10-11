<?php

namespace Tests\Feature;

use Dompdf\Options;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestInvoices;
use Tests\Traits\CreatesTestReceipts;

final class FontRenderingTest extends TestCase
{
    use CreatesTestInvoices;
    use CreatesTestReceipts;

    private ?string $tempFontDir = null;

    protected function tearDown(): void
    {
        if ($this->tempFontDir !== null) {
            File::deleteDirectory($this->tempFontDir);
        }

        parent::tearDown();
    }

    #[Test]
    public function an_invoice_embeds_the_poppins_fonts_by_default(): void
    {
        // act
        $fonts = $this->getEmbeddedFonts($this->createInvoice()->toPdf());

        // assert
        $this->assertEqualsCanonicalizing(['Poppins-Regular', 'Poppins-Medium'], $fonts);
    }

    #[Test]
    public function a_usage_receipt_embeds_the_poppins_fonts_by_default(): void
    {
        // arrange
        $receipt = $this->createReceipt(['note' => 'An italic note']);

        // act
        $fonts = $this->getEmbeddedFonts($receipt->toPdf());

        // assert
        $this->assertEqualsCanonicalizing(['Poppins-Regular', 'Poppins-Medium', 'Poppins-Italic'], $fonts);
    }

    #[Test]
    public function a_custom_font_file_is_used_for_all_text(): void
    {
        // arrange
        Config::set('invoices.pdf.font', 'Custom Font');
        Config::set('invoices.pdf.font_file', $this->createCustomFontFile());

        // act
        $invoiceFonts = $this->getEmbeddedFonts($this->createInvoice()->toPdf());
        $receiptFonts = $this->getEmbeddedFonts($this->createReceipt(['note' => 'An italic note'])->toPdf());

        // assert
        $this->assertSame(['DejaVuSerif'], $invoiceFonts);
        $this->assertSame(['DejaVuSerif'], $receiptFonts);
    }

    #[Test]
    public function a_missing_custom_font_file_falls_back_to_poppins(): void
    {
        // arrange
        Config::set('invoices.pdf.font', 'Custom Font');
        Config::set('invoices.pdf.font_file', '/non/existent/font.ttf');

        // act
        $fonts = $this->getEmbeddedFonts($this->createInvoice()->toPdf());

        // assert
        $this->assertEqualsCanonicalizing(['Poppins-Regular', 'Poppins-Medium'], $fonts);
    }

    #[Test]
    public function the_fonts_are_cached_in_the_configured_font_directory(): void
    {
        // arrange
        $fontDirectory = sys_get_temp_dir().'/laravel-invoices-tests/fonts-'.uniqid();
        $this->tempFontDir = $fontDirectory;
        Config::set('dompdf.options.font_dir', $fontDirectory);
        Config::set('dompdf.options.font_cache', $fontDirectory);

        // act
        $this->createInvoice()->toPdf();

        // assert: the directory is created, and the bundled fonts are cached there instead of in vendor
        $this->assertDirectoryExists($fontDirectory);
        $this->assertNotEmpty(glob($fontDirectory.'/poppins_*'));
    }

    /**
     * Copy a font shipped with dompdf to a directory outside the package
     */
    private function createCustomFontFile(): string
    {
        $this->tempFontDir = sys_get_temp_dir().'/invoices-font-test-'.uniqid();
        File::ensureDirectoryExists($this->tempFontDir);

        $fontFile = $this->tempFontDir.'/CustomFont.ttf';
        File::copy((new Options)->getFontDir().'/DejaVuSerif.ttf', $fontFile);

        return $fontFile;
    }

    /**
     * Get the unique base font names embedded in the pdf output
     *
     * @return array<int, string>
     */
    private function getEmbeddedFonts(string $pdf): array
    {
        preg_match_all('#/BaseFont\s*/(?:[A-Z]{6}\+)?([A-Za-z0-9\-]+)#', $pdf, $matches);

        return array_values(array_unique($matches[1]));
    }
}
