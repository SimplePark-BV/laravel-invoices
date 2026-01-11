<?php

namespace Tests\Traits;

use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Http\Response;
use Mockery;

trait MocksPdfGeneration
{
    protected function mockPdfInstance(string $paperSize = 'a4', string $orientation = 'portrait'): DomPDF
    {
        /** @var \Barryvdh\DomPDF\PDF&\Mockery\MockInterface $mockPdf */
        $mockPdf = Mockery::mock(DomPDF::class);
        $mockPdf->shouldReceive('setPaper')
            ->once()
            ->with($paperSize, $orientation)
            ->andReturnSelf();

        return $mockPdf;
    }

    protected function mockPdfDownload(DomPDF $mockPdf, string $filename): Response
    {
        /** @var \Illuminate\Http\Response&\Mockery\MockInterface $mockResponse */
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('getContent')
            ->andReturn('PDF content');

        /** @var \Barryvdh\DomPDF\PDF&\Mockery\MockInterface $mockPdf */
        $mockPdf->shouldReceive('download')
            ->once()
            ->with($filename)
            ->andReturn($mockResponse);

        return $mockResponse;
    }

    protected function mockPdfStream(DomPDF $mockPdf, string $filename): Response
    {
        /** @var \Illuminate\Http\Response&\Mockery\MockInterface $mockResponse */
        $mockResponse = Mockery::mock(Response::class);
        /** @var \Mockery\MockInterface $mockHeaders */
        $mockHeaders = Mockery::mock();
        $mockResponse->headers = $mockHeaders; // @phpstan-ignore-line property.notFound
        $mockHeaders->shouldReceive('set')
            ->with('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->once();
        $mockHeaders->shouldReceive('set')
            ->with('Pragma', 'no-cache')
            ->once();
        $mockHeaders->shouldReceive('set')
            ->with('Expires', '0')
            ->once();

        /** @var \Barryvdh\DomPDF\PDF&\Mockery\MockInterface $mockPdf */
        $mockPdf->shouldReceive('stream')
            ->once()
            ->with($filename)
            ->andReturn($mockResponse);

        return $mockResponse;
    }
}
