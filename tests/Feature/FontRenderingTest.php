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
    public function invoice_embeds_poppins_fonts_by_default(): void
    {
        // arrange
        $invoice = $this->createValidInvoice();

        // act
        $fonts = $this->getEmbeddedFonts($invoice->render()->pdf?->output() ?? '');

        // assert
        $this->assertEqualsCanonicalizing(['Poppins-Regular', 'Poppins-Medium'], $fonts);
    }

    #[Test]
    public function usage_receipt_embeds_poppins_fonts_by_default(): void
    {
        // arrange
        $receipt = $this->createValidReceipt();
        $receipt->note('Italic note');

        // act
        $fonts = $this->getEmbeddedFonts($receipt->render()->pdf?->output() ?? '');

        // assert
        $this->assertEqualsCanonicalizing(['Poppins-Regular', 'Poppins-Medium', 'Poppins-Italic'], $fonts);
    }

    #[Test]
    public function invoice_uses_custom_font_file_for_all_text(): void
    {
        // arrange
        Config::set('invoices.pdf.font', 'Custom Font');
        Config::set('invoices.pdf.font_file', $this->createCustomFontFile());

        $invoice = $this->createValidInvoice();

        // act
        $fonts = $this->getEmbeddedFonts($invoice->render()->pdf?->output() ?? '');

        // assert
        $this->assertSame(['DejaVuSerif'], $fonts);
    }

    #[Test]
    public function usage_receipt_uses_custom_font_file_for_all_text(): void
    {
        // arrange
        Config::set('invoices.pdf.font', 'Custom Font');
        Config::set('invoices.pdf.font_file', $this->createCustomFontFile());

        $receipt = $this->createValidReceipt();
        $receipt->note('Italic note');

        // act
        $fonts = $this->getEmbeddedFonts($receipt->render()->pdf?->output() ?? '');

        // assert
        $this->assertSame(['DejaVuSerif'], $fonts);
    }

    /**
     * Copy a font shipped with dompdf to a directory outside the package root
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
