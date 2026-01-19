<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use RuntimeException;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;

/**
 * Trait HasPdfRendering
 *
 * provides common pdf rendering, downloading, and streaming functionality
 */
trait HasPdfRendering
{
    public ?DomPDF $pdf = null;

    public ?string $output = null;

    /**
     * @var array<string, mixed>
     */
    public array $options = [];

    /**
     * @var array<string, string>
     */
    public array $paperOptions = [];

    /**
     * get the default filename for the document
     */
    abstract public function getFilename(): string;

    /**
     * get the view variable name to use when rendering
     * e.g., 'invoice' or 'usageReceipt'
     */
    abstract protected function getViewVariableName(): string;

    /**
     * validate the document before rendering
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     */
    abstract public function validate(): void;

    /**
     * check if the pdf has been rendered
     */
    public function isRendered(): bool
    {
        return $this->pdf !== null;
    }

    /**
     * clear the pdf instance to free memory
     */
    public function clearPdf(): self
    {
        $this->pdf = null;

        return $this;
    }

    /**
     * generate the pdf instance
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     */
    public function render(): self
    {
        // validate document before rendering
        $this->validate();

        // save current locale
        $originalLocale = App::getLocale();

        // set locale for this document
        App::setLocale($this->getLanguage());

        try {
            $template = sprintf('invoices::%s', $this->template);

            // get the package root directory to allow dompdf to access fonts
            $packageRoot = realpath(__DIR__.'/../../../');

            if ($packageRoot === false) {
                throw new RuntimeException(
                    'Failed to resolve package root directory. The path '.__DIR__.'/../../../ could not be resolved to a valid directory.'
                );
            }

            $viewVariableName = $this->getViewVariableName();

            $this->pdf = Pdf::setOptions([
                'chroot' => $packageRoot,
                'isRemoteEnabled' => false,
            ])
                ->loadView($template, [$viewVariableName => $this])
                ->setPaper($this->paperOptions['size'], $this->paperOptions['orientation']);
        } catch (\Throwable $e) {
            $this->pdf = null;
            throw new InvalidInvoiceException('Failed to render PDF: '.$e->getMessage(), 0, $e);
        } finally {
            App::setLocale($originalLocale);
        }

        return $this;
    }

    /**
     * download the document as a pdf
     */
    public function download(?string $filename = null): Response
    {
        if (! $this->pdf) {
            $this->render();
        }

        if (! $this->pdf) {
            throw new InvalidInvoiceException('Failed to render PDF');
        }

        $filename = $filename ?? $this->getFilename();

        return $this->pdf->download($filename);
    }

    /**
     * stream the document in the browser (good for testing/previewing)
     */
    public function stream(?string $filename = null): Response
    {
        if (! $this->pdf) {
            $this->render();
        }

        if (! $this->pdf) {
            throw new InvalidInvoiceException('Failed to render PDF');
        }

        $filename = $filename ?? $this->getFilename();

        $response = $this->pdf->stream($filename);

        // add cache-busting headers for development
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
