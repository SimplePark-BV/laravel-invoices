<?php

namespace Tests\Traits;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Http\Response;
use Mockery;

trait MocksPdfGeneration
{
    protected function mockPdfInstance(string $paperSize = 'a4', string $orientation = 'portrait'): DomPDF
    {
        $mockPdf = Mockery::mock(DomPDF::class);
        $mockPdf->shouldReceive('setPaper')
            ->once()
            ->with($paperSize, $orientation)
            ->andReturnSelf();

        return $mockPdf;
    }

    protected function mockPdfDownload(DomPDF $mockPdf, string $filename): Response
    {
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('getContent')
            ->andReturn('PDF content');

        $mockPdf->shouldReceive('download')
            ->once()
            ->with($filename)
            ->andReturn($mockResponse);

        return $mockResponse;
    }

    protected function mockPdfStream(DomPDF $mockPdf, string $filename): Response
    {
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->headers = Mockery::mock();
        $mockResponse->headers->shouldReceive('set')
            ->with('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->once();
        $mockResponse->headers->shouldReceive('set')
            ->with('Pragma', 'no-cache')
            ->once();
        $mockResponse->headers->shouldReceive('set')
            ->with('Expires', '0')
            ->once();

        $mockPdf->shouldReceive('stream')
            ->once()
            ->with($filename)
            ->andReturn($mockResponse);

        return $mockResponse;
    }
}
