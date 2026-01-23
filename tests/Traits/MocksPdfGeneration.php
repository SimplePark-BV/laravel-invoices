<?php

namespace Tests\Traits;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Http\Response;
use Mockery;
use ReflectionClass;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

trait MocksPdfGeneration
{
    /**
     * Mock the Pdf facade chain: setOptions() -> loadView() -> setPaper()
     * This sets up setOptions() to return the facade itself so it can chain to loadView()
     */
    protected function mockPdfFacadeChain(): void
    {
        // mock setOptions to return the facade itself so it can chain
        // this allows Pdf::setOptions()->loadView() to work
        // we use zeroOrMoreTimes() to allow it to work with other expectations
        /** @phpstan-ignore-next-line method.notFound */
        Pdf::shouldReceive('setOptions')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
    }

    protected function mockPdfInstance(string $paperSize = 'a4', string $orientation = 'portrait'): DomPDF
    {
        /** @var \Barryvdh\DomPDF\PDF&\Mockery\MockInterface $mockPdf */
        $mockPdf = Mockery::mock(DomPDF::class);

        /** @phpstan-ignore-next-line method.notFound */
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

        /** @phpstan-ignore-next-line method.notFound */
        $mockResponse->shouldReceive('getContent')
            ->andReturn('PDF content');

        /** @var \Barryvdh\DomPDF\PDF&\Mockery\MockInterface $mockPdf */

        /** @phpstan-ignore-next-line method.notFound */
        $mockPdf->shouldReceive('download')
            ->once()
            ->with($filename)
            ->andReturn($mockResponse);

        return $mockResponse;
    }

    protected function mockPdfStream(DomPDF $mockPdf, string $filename): Response
    {
        /** @var \Illuminate\Http\Response&\Mockery\MockInterface $mockResponse */
        $mockResponse = Mockery::mock(Response::class)->makePartial();

        /** @var ResponseHeaderBag&\Mockery\MockInterface $mockHeaders */
        $mockHeaders = Mockery::mock(ResponseHeaderBag::class);

        /** @phpstan-ignore-next-line method.notFound */
        $mockHeaders->shouldReceive('set')
            ->with('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->once()
            ->andReturnSelf();

        /** @phpstan-ignore-next-line method.notFound */
        $mockHeaders->shouldReceive('set')
            ->with('Pragma', 'no-cache')
            ->once()
            ->andReturnSelf();

        /** @phpstan-ignore-next-line method.notFound */
        $mockHeaders->shouldReceive('set')
            ->with('Expires', '0')
            ->once()
            ->andReturnSelf();

        // allow get() calls to return the expected header values
        /** @phpstan-ignore-next-line method.notFound */
        $mockHeaders->shouldReceive('get')
            ->andReturnUsing(function (string $key) {
                return match ($key) {
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    default => null,
                };
            });

        // use reflection to set the headers property
        $reflection = new ReflectionClass($mockResponse);
        $property = $reflection->getProperty('headers');
        $property->setValue($mockResponse, $mockHeaders);

        /** @var \Barryvdh\DomPDF\PDF&\Mockery\MockInterface $mockPdf */
        /** @phpstan-ignore-next-line method.notFound */
        $mockPdf->shouldReceive('stream')
            ->once()
            ->with($filename)
            ->andReturn($mockResponse);

        return $mockResponse;
    }
}
