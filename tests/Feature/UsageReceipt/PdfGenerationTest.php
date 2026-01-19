<?php

namespace Tests\Feature\UsageReceipt;

use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Models\Buyer;
use SimpleParkBv\Invoices\Models\ReceiptItem;
use SimpleParkBv\Invoices\Models\UsageReceipt;
use Tests\TestCase;
use Tests\Traits\CreatesTestReceipts;
use Tests\Traits\MocksPdfGeneration;

final class PdfGenerationTest extends TestCase
{
    use CreatesTestReceipts;
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
        $receipt = $this->create_valid_receipt();
        $mockPdf = $this->mockPdfInstance();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('invoices::usage-receipt.index', ['usageReceipt' => $receipt])
            ->andReturn($mockPdf);

        // act
        $result = $receipt->render();

        // assert
        $this->assertSame($receipt, $result);
        $this->assertTrue($receipt->isRendered());
    }

    #[Test]
    public function render_sets_locale_during_rendering(): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();
        $receipt->language('en');
        App::setLocale('nl'); // set different locale

        $mockPdf = $this->mockPdfInstance();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturnUsing(function ($view, $data) use ($mockPdf) {
                // verify locale is set to receipt language during rendering
                $this->assertEquals('en', App::getLocale());

                return $mockPdf;
            });

        // act
        $receipt->render();

        // assert
        // verify original locale is restored
        $this->assertEquals('nl', App::getLocale());
    }

    #[Test]
    #[DataProvider('template_data_provider')]
    public function render_uses_template(string $template, string $expectedTemplate): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();
        $receipt->template = $template;
        $mockPdf = $this->mockPdfInstance();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->with($expectedTemplate, ['usageReceipt' => $receipt])
            ->andReturn($mockPdf);

        // act
        $receipt->render();

        // assert
        // verified in mock expectation - ensure receipt was rendered
        $this->assertTrue($receipt->isRendered());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function template_data_provider(): array
    {
        return [
            'default template' => ['usage-receipt.index', 'invoices::usage-receipt.index'],
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

        $receipt = UsageReceipt::make();
        $buyer = Buyer::make(['name' => 'Test Buyer']);
        $receipt->buyer($buyer);

        $item = ReceiptItem::make([
            'user' => 'John Doe',
            'identifier' => 'ABC-123',
            'start_date' => '2024-01-15 10:00:00',
            'end_date' => '2024-01-15 12:00:00',
            'category' => 'Standard Parking',
            'price' => 5.50,
        ]);
        $receipt->items([$item]);

        $mockPdf = $this->mockPdfInstance($paperSize, $orientation);
        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);

        // act
        $receipt->render();

        // assert
        // verified in mock expectation - ensure receipt was rendered
        $this->assertTrue($receipt->isRendered());
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
        $receipt = $this->create_valid_receipt();
        $receipt->date('2024-01-15'); // fixed date for consistent testing
        $mockPdf = $this->mockPdfInstance();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);
        $this->mockPdfDownload($mockPdf, $expectedFilename);

        // act
        $response = $receipt->download($customFilename);

        // assert
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public static function download_filename_data_provider(): array
    {
        return [
            'default filename' => [null, 'parkeerbevestiging-15-01-2024.pdf'],
            'custom filename' => ['custom-receipt.pdf', 'custom-receipt.pdf'],
        ];
    }

    #[Test]
    public function download_auto_renders(): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();
        $receipt->date('2024-01-15'); // fixed date for consistent testing
        $mockPdf = $this->mockPdfInstance();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);
        $this->mockPdfDownload($mockPdf, 'parkeerbevestiging-15-01-2024.pdf');

        // assert
        // pdf not rendered yet
        $this->assertFalse($receipt->isRendered());

        // act
        $receipt->download();

        // assert
        // pdf should be rendered now
        $this->assertTrue($receipt->isRendered());
    }

    #[Test]
    public function download_throws_when_render_fails(): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andThrow(new Exception('PDF generation failed')); // simulate render failure

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Failed to render PDF');

        // act
        $receipt->download();
    }

    #[Test]
    public function download_uses_correct_filename_format(): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();
        $receipt->date('2025-09-25'); // specific date for filename pattern verification
        $mockPdf = $this->mockPdfInstance();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);
        $this->mockPdfDownload($mockPdf, 'parkeerbevestiging-25-09-2025.pdf');

        // act
        $response = $receipt->download();

        // assert
        $this->assertInstanceOf(Response::class, $response);
    }

    #[Test]
    #[DataProvider('stream_filename_data_provider')]
    public function stream_generates_response(?string $customFilename, string $expectedFilename): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();
        $receipt->date('2024-01-15'); // fixed date for consistent testing
        $mockPdf = $this->mockPdfInstance();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);

        $this->mockPdfStream($mockPdf, $expectedFilename);

        // act
        $response = $receipt->stream($customFilename);

        // assert
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public static function stream_filename_data_provider(): array
    {
        return [
            'default filename' => [null, 'parkeerbevestiging-15-01-2024.pdf'],
            'custom filename' => ['custom-receipt.pdf', 'custom-receipt.pdf'],
        ];
    }

    #[Test]
    public function stream_includes_cache_headers(): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();
        $receipt->date('2024-01-15'); // fixed date for consistent testing
        $mockPdf = $this->mockPdfInstance();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);

        $this->mockPdfStream($mockPdf, 'parkeerbevestiging-15-01-2024.pdf');

        // act
        $response = $receipt->stream();

        // assert
        $this->assertTrue($receipt->isRendered());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('no-cache, no-store, must-revalidate', $response->headers->get('Cache-Control'));
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
        $this->assertEquals('0', $response->headers->get('Expires'));
    }

    #[Test]
    public function stream_auto_renders(): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();
        $receipt->date('2024-01-15'); // fixed date for consistent testing
        $mockPdf = $this->mockPdfInstance();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);

        $this->mockPdfStream($mockPdf, 'parkeerbevestiging-15-01-2024.pdf');

        // assert
        // pdf not rendered yet
        $this->assertFalse($receipt->isRendered());

        // act
        $receipt->stream();

        // assert
        // pdf should be rendered now
        $this->assertTrue($receipt->isRendered());
    }

    #[Test]
    public function stream_throws_when_render_fails(): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();
        $this->mockPdfFacadeChain();
        Pdf::shouldReceive('loadView')
            ->once()
            ->andThrow(new Exception('PDF generation failed')); // simulate render failure

        // assert
        $this->expectException(InvalidInvoiceException::class);
        $this->expectExceptionMessage('Failed to render PDF');

        // act
        $receipt->stream();
    }

    #[Test]
    public function stream_uses_correct_filename_format(): void
    {
        // arrange
        $receipt = $this->create_valid_receipt();
        $receipt->date('2025-09-25'); // specific date for filename pattern verification
        $mockPdf = $this->mockPdfInstance();

        $this->mockPdfFacadeChain();

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($mockPdf);

        $this->mockPdfStream($mockPdf, 'parkeerbevestiging-25-09-2025.pdf');

        // act
        $response = $receipt->stream();

        // assert
        $this->assertInstanceOf(Response::class, $response);
    }
}
