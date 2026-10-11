<?php

namespace Tests\Feature\Invoice;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\RenderException;
use SimpleParkBv\Invoices\Services\PdfRenderer;
use Tests\TestCase;
use Tests\Traits\CreatesTestInvoices;

final class PdfGenerationTest extends TestCase
{
    use CreatesTestInvoices;

    #[Test]
    public function the_html_shows_every_section_of_an_issued_invoice(): void
    {
        // arrange
        $invoice = $this->createFullInvoice();

        // act
        $html = $invoice->toHtml();

        // assert: header with logo and seller
        $this->assertStringContainsString('<img class="document__logo" src="data:image/png;base64,', $html);
        $this->assertStringContainsString('Test Seller B.V.', $html);
        $this->assertStringContainsString('Teststraat 1<br>1234 AB Teststad<br>Nederland', $html);
        $this->assertStringContainsString('KvK: 12345678', $html);
        $this->assertStringContainsString('Btw: NL123456789B01', $html);
        $this->assertStringContainsString('Bank: NL00 TEST 0123 4567 89', $html);

        // assert: buyer
        $this->assertStringContainsString('Jan de Vries', $html);
        $this->assertStringContainsString('Keizersgracht 123<br>1015 CJ Amsterdam<br>Nederland', $html);
        $this->assertStringContainsString('jan@example.test<br>+31 6 12345678', $html);

        // assert: title and dates
        $this->assertStringContainsString('Factuur 2026.0001', $html);
        $this->assertStringContainsString('Factuurdatum:', $html);
        $this->assertStringContainsString('Vervaldatum:', $html);
        $this->assertStringContainsString('10-11-2026', $html);

        // assert: items, with localized quantities and percentages
        $this->assertStringContainsString('Zone 1001, AB-123-C', $html);
        $this->assertStringContainsString('1,5x', $html);
        $this->assertStringContainsString("€\u{00A0}24,20", $html);
        $this->assertStringContainsString("€\u{00A0}22,19", $html);
        $this->assertStringContainsString("€\u{00A0}-1,00", $html);

        // assert: totals with the taxable amount per rate
        $this->assertStringContainsString('Subtotaal excl. btw', $html);
        $this->assertStringContainsString("21% btw over €\u{00A0}19,17", $html);
        $this->assertStringContainsString("9% btw over €\u{00A0}20,36", $html);
        $this->assertStringContainsString("€\u{00A0}49,89", $html);

        // assert: footer
        $this->assertStringContainsString('te voldoen op onze bankrekening', $html);

        // assert: every translation was found
        $this->assertStringNotContainsString('invoices::', $html);
    }

    #[Test]
    #[DataProvider('variants')]
    public function every_variant_renders_without_missing_translations(string $language, bool $issued, string $title): void
    {
        // arrange
        $invoice = $this->createFullInvoice(['language' => $language, 'date' => $issued ? '2026-10-11' : null]);

        // act
        $html = $invoice->toHtml();

        // assert
        $this->assertStringContainsString($title, $html);
        $this->assertStringContainsString('<html lang="'.$language.'">', $html);
        $this->assertStringNotContainsString('invoices::', $html);
    }

    /**
     * @return array<string, array{string, bool, string}>
     */
    public static function variants(): array
    {
        return [
            'dutch concept' => ['nl', false, 'Factuur 2026.0001'],
            'dutch issued' => ['nl', true, 'Factuur 2026.0001'],
            'english concept' => ['en', false, 'Invoice 2026.0001'],
            'english issued' => ['en', true, 'Invoice 2026.0001'],
        ];
    }

    #[Test]
    public function a_concept_has_no_dates_and_says_it_is_a_concept(): void
    {
        // arrange
        $invoice = $this->createInvoice();

        // act
        $html = $invoice->toHtml();

        // assert
        $this->assertStringContainsString('Factuur CONCEPT', $html);
        $this->assertStringNotContainsString('Factuurdatum:', $html);
        $this->assertStringContainsString('Dit is een <strong>CONCEPT</strong>', $html);
    }

    #[Test]
    public function a_paid_invoice_has_no_due_date(): void
    {
        // arrange
        $invoice = $this->createFullInvoice(['paid' => true]);

        // act
        $html = $invoice->toHtml();

        // assert
        $this->assertStringContainsString('Factuurdatum:', $html);
        $this->assertStringNotContainsString('Vervaldatum:', $html);
        $this->assertStringContainsString('is al voldaan', $html);
    }

    #[Test]
    public function a_credit_invoice_is_titled_as_one(): void
    {
        // arrange
        $invoice = $this->createInvoice(['serial' => '2026.0002', 'date' => '2026-10-11', 'items' => [['title' => 'Refund', 'unit_price' => -12.10, 'tax_percentage' => 21]]]);

        // act
        $html = $invoice->toHtml();

        // assert
        $this->assertStringContainsString('Creditfactuur 2026.0002', $html);
        $this->assertStringNotContainsString('Vervaldatum:', $html);
        $this->assertStringContainsString('overmaken op je bankrekening', $html);
    }

    #[Test]
    public function caller_data_is_escaped(): void
    {
        // arrange
        $invoice = $this->createInvoice([
            'buyer' => ['name' => '<script>alert(1)</script>'],
            'items' => [['title' => '<img src="file:///etc/passwd">', 'description' => "Line 1\n<b>Line 2</b>", 'unit_price' => 1]],
            'date' => '2026-10-11',
            'footer_message' => '<a href="https://example.test">Pay</a> :amount',
        ]);

        // act
        $html = $invoice->toHtml();

        // assert
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src="file:', $html);
        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringContainsString('Line 1<br />'."\n".'&lt;b&gt;Line 2&lt;/b&gt;', $html);
    }

    #[Test]
    public function without_a_logo_nothing_is_drawn_in_its_place(): void
    {
        // arrange
        $invoice = $this->createFullInvoice(['logo' => null]);

        // act
        $html = $invoice->toHtml();

        // assert
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('placeholder', $html);
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function to_pdf_renders_a_pdf_with_the_bundled_fonts(): void
    {
        // arrange
        $invoice = $this->createFullInvoice();

        // act
        $pdf = $invoice->toPdf();

        // assert
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('/MediaBox [0.000 0.000 595.280 841.890]', $pdf);
        $this->assertStringContainsString('Poppins', $pdf);
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function every_output_renders_a_fresh_pdf(): void
    {
        // arrange
        $invoice = $this->createFullInvoice();

        // act
        $first = $invoice->toPdf();
        $second = $invoice->toPdf();

        // assert: outputting a rendered dompdf instance twice used to embed the fonts twice and corrupt the text
        $this->assertEqualsWithDelta(strlen($first), strlen($second), strlen($first) * 0.01);
        $this->assertSame(substr_count($first, '/FontFile2'), substr_count($second, '/FontFile2'));
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function changes_after_an_output_are_rendered(): void
    {
        // arrange
        $invoice = $this->createFullInvoice();
        $invoice->toPdf();

        // act
        $html = $invoice->language('en')->toHtml();

        // assert
        $this->assertStringContainsString('Invoice 2026.0001', $html);
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function download_returns_an_attachment_response(): void
    {
        // arrange
        $invoice = $this->createFullInvoice();

        // act
        $response = $invoice->download();

        // assert
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename=factuur-2026.0001.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function stream_returns_an_inline_response(): void
    {
        // arrange
        $invoice = $this->createFullInvoice();

        // act
        $response = $invoice->stream();

        // assert
        $this->assertSame('inline; filename=factuur-2026.0001.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function a_custom_filename_is_made_safe(): void
    {
        // arrange
        $invoice = $this->createFullInvoice(['serial' => '2026/0001']);

        // act
        $default = $invoice->download();
        $custom = $invoice->download('my/invoice.pdf');

        // assert
        $this->assertSame('attachment; filename=factuur-2026-0001.pdf', $default->headers->get('Content-Disposition'));
        $this->assertSame('attachment; filename=my-invoice.pdf', $custom->headers->get('Content-Disposition'));
    }

    #[Test]
    public function a_missing_template_throws_a_render_exception(): void
    {
        // arrange
        $invoice = $this->createInvoice(['template' => 'invoices::missing']);

        // assert
        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('Failed to render invoice (concept): View [missing] not found.');

        // act
        $invoice->toPdf();
    }

    #[Test]
    public function a_template_of_the_app_can_be_used(): void
    {
        // arrange
        $directory = sys_get_temp_dir().'/laravel-invoices-tests/views';
        @mkdir($directory, 0777, true);
        file_put_contents($directory.'/custom-invoice.blade.php', 'Custom {{ $invoice->getTitle() }} {{ $format->money($totals->getTotalCents()) }}');
        $this->app?->make('view')->addLocation($directory);

        // act
        $html = $this->createInvoice(['template' => 'custom-invoice'])->toHtml();

        // assert
        $this->assertSame("Custom Factuur CONCEPT €\u{00A0}10,00", $html);
    }

    #[Test]
    public function rendering_keeps_the_dompdf_config_of_the_app(): void
    {
        // arrange
        config()->set('dompdf.options.dpi', 150);
        config()->set('dompdf.options.enable_remote', true);
        config()->set('dompdf.options.enable_javascript', true);

        // act
        $options = app(PdfRenderer::class)->pdf($this->createInvoice())->getDomPDF()->getOptions();

        // assert: the config of the app is kept, the package turns off what it never needs
        $this->assertSame(150, $options->getDpi());
        $this->assertSame(sys_get_temp_dir().'/laravel-invoices-tests/fonts', $options->getFontDir());
        $this->assertFalse($options->getIsRemoteEnabled());
        $this->assertFalse($options->getIsJavascriptEnabled());
        $this->assertFalse($options->getIsPhpEnabled());
        $this->assertContains((string) realpath(__DIR__.'/../../../resources'), $options->getChroot());
        $this->assertContains((string) realpath(base_path()), $options->getChroot());
    }

    #[Test]
    public function rendering_does_not_change_the_locale_of_the_app(): void
    {
        // arrange
        app()->setLocale('en');
        app('translator')->setLocale('fr');
        $events = [];
        app('events')->listen(\Illuminate\Foundation\Events\LocaleUpdated::class, function ($event) use (&$events): void {
            $events[] = $event;
        });

        // act
        $html = $this->createInvoice(['language' => 'nl'])->toHtml();

        // assert
        $this->assertStringContainsString('Factuur CONCEPT', $html);
        $this->assertSame('en', app()->getLocale());
        $this->assertSame('fr', app('translator')->getLocale());
        $this->assertSame([], $events);
    }

    #[Test]
    public function the_locale_is_restored_when_rendering_fails(): void
    {
        // arrange
        app('translator')->setLocale('en');

        // act
        try {
            $this->createInvoice(['template' => 'invoices::missing', 'language' => 'nl'])->toHtml();
        } catch (RenderException) {
            // expected
        }

        // assert
        $this->assertSame('en', app('translator')->getLocale());
    }
}
