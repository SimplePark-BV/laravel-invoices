<?php

namespace Tests\Feature\UsageReceipt;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\RenderException;
use Tests\TestCase;
use Tests\Traits\CreatesTestReceipts;

final class PdfGenerationTest extends TestCase
{
    use CreatesTestReceipts;

    #[Test]
    public function the_html_shows_every_section_of_a_receipt(): void
    {
        // arrange
        $receipt = $this->createFullReceipt();

        // act
        $html = $receipt->toHtml();

        // assert: header, title and date
        $this->assertStringContainsString('<img class="document__logo" src="data:image/png;base64,', $html);
        $this->assertStringContainsString('Gebruiksbevestiging', $html);
        $this->assertStringContainsString('11-10-2026', $html);

        // assert: buyer and ids
        $this->assertStringContainsString('Keizersgracht 123<br>1015 CJ Amsterdam', $html);
        $this->assertStringContainsString('Document-ID', $html);
        $this->assertStringContainsString('PS-2026-000123', $html);
        $this->assertStringContainsString('Gebruikers-ID', $html);
        $this->assertStringContainsString('USR-000042', $html);

        // assert: sessions
        $this->assertStringContainsString('02-10-2026 10:00', $html);
        $this->assertStringContainsString('03-10-2026 13:30', $html);
        $this->assertStringContainsString("€\u{00A0}5,50", $html);
        $this->assertStringContainsString("€\u{00A0}7,00", $html);

        // assert: lines, the total, and the tax the total includes
        $this->assertStringContainsString('Parking costs', $html);
        $this->assertStringContainsString("€\u{00A0}-0,10", $html);
        $this->assertStringContainsString("€\u{00A0}12,75", $html);
        $this->assertStringContainsString('21% btw', $html);
        $this->assertStringContainsString("€\u{00A0}0,04", $html);

        // assert: note with its line break, and the footer
        $this->assertStringContainsString('Thank you for parking.<br />', $html);
        $this->assertStringContainsString('LET OP! Dit is geen betaalverzoek', $html);
        $this->assertStringContainsString('KvK-nummer', $html);
        $this->assertStringContainsString('Btw-nummer', $html);
        $this->assertStringContainsString('www.seller.test', $html);

        // assert: every translation was found
        $this->assertStringNotContainsString('invoices::', $html);
    }

    #[Test]
    public function the_tax_rows_come_after_the_total(): void
    {
        // arrange
        $receipt = $this->createFullReceipt();

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertGreaterThan(strpos($html, 'class="usage-receipt__totals-label usage-receipt__totals-label--total"'), strpos($html, '21% btw'));
    }

    #[Test]
    #[DataProvider('languages')]
    public function every_language_renders_without_missing_translations(string $language, string $title, string $amount): void
    {
        // arrange
        $receipt = $this->createFullReceipt(['language' => $language]);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertStringContainsString($title, $html);
        $this->assertStringContainsString($amount, $html);
        $this->assertStringNotContainsString('invoices::', $html);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function languages(): array
    {
        return [
            'dutch' => ['nl', 'Gebruiksbevestiging', "€\u{00A0}12,75"],
            'english' => ['en', 'Usage Confirmation', "€\u{00A0}12.75"],
        ];
    }

    #[Test]
    public function session_times_are_shown_in_the_display_timezone(): void
    {
        // arrange
        $receipt = $this->createReceipt(['items' => [$this->receiptItemData([
            'start_date' => '2026-07-01T08:00:00Z',
            'end_date' => '2026-07-01T09:30:00Z',
        ])]]);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertStringContainsString('01-07-2026 10:00', $html);
        $this->assertStringContainsString('01-07-2026 11:30', $html);
    }

    #[Test]
    public function caller_data_is_escaped(): void
    {
        // arrange
        $receipt = $this->createReceipt([
            'title' => '<script>alert(1)</script>',
            'note' => '<img src="file:///etc/passwd">',
            'lines' => [['label' => '<b>Fee</b>', 'amount' => 1]],
        ]);

        // act
        $html = $receipt->toHtml();

        // assert
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src="file:', $html);
        $this->assertStringNotContainsString('<b>Fee</b>', $html);
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function to_pdf_renders_a_pdf(): void
    {
        // act
        $pdf = $this->createFullReceipt()->toPdf();

        // assert
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    #[Test]
    #[RequiresPhpExtension('gd')]
    public function download_and_stream_use_the_document_id_as_filename(): void
    {
        // arrange
        $receipt = $this->createFullReceipt();

        // act
        $download = $receipt->download();
        $stream = $receipt->stream();

        // assert
        $this->assertSame('attachment; filename=gebruiksbevestiging-PS-2026-000123.pdf', $download->headers->get('Content-Disposition'));
        $this->assertSame('inline; filename=gebruiksbevestiging-PS-2026-000123.pdf', $stream->headers->get('Content-Disposition'));
    }

    #[Test]
    public function a_render_failure_throws_a_render_exception_for_the_receipt(): void
    {
        // arrange
        $receipt = $this->createFullReceipt(['template' => 'invoices::missing']);

        // assert
        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('Failed to render usage receipt PS-2026-000123');

        // act
        $receipt->toPdf();
    }
}
