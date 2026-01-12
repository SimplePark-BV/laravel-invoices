<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Buyer;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Invoice;
use SimpleParkBv\Invoices\InvoiceItem;
use Tests\TestCase;
use Tests\Traits\CreatesTestInvoices;
use Tests\Traits\MocksPdfGeneration;

final class InvoicePdfGenerationTest extends TestCase
{
    use CreatesTestInvoices;
    use MocksPdfGeneration;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function render_creates_pdf(): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();
        $mockPdf = $this->mockPdfInstance();
        $this->mockPdfFacadeChain($mockPdf);
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('invoices::invoice.index', ['invoice' => $invoice])
            ->andReturn($mockPdf);

        // act
        $result = $invoice->render();

        // assert
        $this->assertSame($invoice, $result);
        $this->assertTrue($invoice->isRendered());
    }

    #[Test]
    public function render_sets_locale_during_rendering(): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();
        $invoice->language('en');
        App::setLocale('nl'); // set different locale

        $mockPdf = $this->mockPdfInstance();
        $this->mockPdfFacadeChain($mockPdf);
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturnUsing(function ($view, $data) use ($mockPdf) {
                // verify locale is set to invoice language during rendering
                $this->assertEquals('en', App::getLocale());

                return $mockPdf;
            });

        // act
        $invoice->render();

        // assert
        // verify original locale is restored
        $this->assertEquals('nl', App::getLocale());
    }

    #[Test]
    #[DataProvider('template_data_provider')]
    public function render_uses_template(string $template, string $expectedTemplate): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();
        $invoice->template = $template;
        $mockPdf = $this->mockPdfInstance();
        $this->mockPdfFacadeChain($mockPdf);
        Pdf::shouldReceive('loadView')
            ->once()
            ->with($expectedTemplate, ['invoice' => $invoice])
            ->andReturn($mockPdf);

        // act
        $invoice->render();

        // assert
        // verified in mock expectation - ensure invoice was rendered
        $this->assertTrue($invoice->isRendered());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function template_data_provider(): array
    {
        return [
            'default template' => ['invoice.index', 'invoices::invoice.index'],
            'custom template' => ['custom-template', 'invoices::custom-template'],
        ];
    }

    #[Test]
    #[DataProvider('paper_config_data_provider')]
    public function render_sets_paper_from_config(string $paperSize, string $orientation): void
    {
        // arrange
        Config::set('invoices.pdf.paper_size', $paperSize);
        Config::set('invoices.pdf.orientation', $orientation);

        $invoice = Invoice::make();
        $buyer = Buyer::make();
        $buyer->name = 'Test Buyer';
        $invoice->buyer($buyer);

        $item = InvoiceItem::make();
        $item->title = 'Item';
        $item->quantity = 1;
        $item->unit_price = 10.00;
        $invoice->items([$item]);

        $mockPdf = $this->mockPdfInstance($paperSize, $orientation);
        $this->mockPdfFacadeChain($mockPdf);
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);

        // act
        $invoice->render();

        // assert
        // verified in mock expectation - ensure invoice was rendered
        $this->assertTrue($invoice->isRendered());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function paper_config_data_provider(): array
    {
        return [
            'letter landscape' => ['letter', 'landscape'],
            'a4 portrait' => ['a4', 'portrait'],
        ];
    }

    #[Test]
    #[DataProvider('download_filename_data_provider')]
    public function download_generates_response(?string $customFilename, string $expectedFilename): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();
        $invoice->date('2024-01-15'); // Fixed date for consistent testing
        $mockPdf = $this->mockPdfInstance();
        $this->mockPdfFacadeChain($mockPdf);
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);
        $this->mockPdfDownload($mockPdf, $expectedFilename);

        // act
        $response = $invoice->download($customFilename);

        // assert
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public static function download_filename_data_provider(): array
    {
        return [
            'default filename' => [null, 'invoice-20240115.pdf'],
            'custom filename' => ['custom-invoice.pdf', 'custom-invoice.pdf'],
        ];
    }

    #[Test]
    public function download_auto_renders(): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();
        $invoice->date('2024-01-15'); // Fixed date for consistent testing
        $mockPdf = $this->mockPdfInstance();
        $this->mockPdfFacadeChain($mockPdf);
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);
        $this->mockPdfDownload($mockPdf, 'invoice-20240115.pdf');

        // assert
        // pdf not rendered yet
        $this->assertFalse($invoice->isRendered());

        // act
        $invoice->download();

        // assert
        // pdf should be rendered now
        $this->assertTrue($invoice->isRendered());
    }

    #[Test]
    public function download_throws_when_render_fails(): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();
        $this->mockPdfFacadeChain();
        Pdf::shouldReceive('loadView')
            ->once()
            ->andThrow(new Exception('PDF generation failed')); // simulate render failure

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Failed to render PDF');

        // act
        $invoice->download();
    }

    #[Test]
    #[DataProvider('stream_filename_data_provider')]
    public function stream_generates_response(?string $customFilename, string $expectedFilename): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();
        $invoice->date('2024-01-15'); // Fixed date for consistent testing
        $mockPdf = $this->mockPdfInstance();
        $this->mockPdfFacadeChain($mockPdf);
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);
        $this->mockPdfStream($mockPdf, $expectedFilename);

        // act
        $response = $invoice->stream($customFilename);

        // assert
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public static function stream_filename_data_provider(): array
    {
        return [
            'default filename' => [null, 'invoice-20240115.pdf'],
            'custom filename' => ['custom-invoice.pdf', 'custom-invoice.pdf'],
        ];
    }

    #[Test]
    public function stream_includes_cache_headers(): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();
        $invoice->date('2024-01-15'); // Fixed date for consistent testing
        $mockPdf = $this->mockPdfInstance();
        $this->mockPdfFacadeChain($mockPdf);
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);
        $this->mockPdfStream($mockPdf, 'invoice-20240115.pdf');

        // act
        $response = $invoice->stream();

        // assert
        $this->assertTrue($invoice->isRendered());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('no-cache, no-store, must-revalidate', $response->headers->get('Cache-Control'));
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
        $this->assertEquals('0', $response->headers->get('Expires'));
    }

    #[Test]
    public function stream_auto_renders(): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();
        $invoice->date('2024-01-15'); // Fixed date for consistent testing
        $mockPdf = $this->mockPdfInstance();
        $this->mockPdfFacadeChain($mockPdf);
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);
        $this->mockPdfStream($mockPdf, 'invoice-20240115.pdf');

        // assert
        // pdf not rendered yet
        $this->assertFalse($invoice->isRendered());

        // act
        $invoice->stream();

        // assert
        // pdf should be rendered now
        $this->assertTrue($invoice->isRendered());
    }

    #[Test]
    public function stream_throws_when_render_fails(): void
    {
        // arrange
        $invoice = $this->create_valid_invoice();
        $this->mockPdfFacadeChain();
        Pdf::shouldReceive('loadView')
            ->once()
            ->andThrow(new Exception('PDF generation failed')); // simulate render failure

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Failed to render PDF');

        // act
        $invoice->stream();
    }
}
