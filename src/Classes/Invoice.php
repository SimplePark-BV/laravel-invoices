<?php

namespace SimpleParkBv\Invoices;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use SimpleParkBv\Invoices\Traits\HasInvoiceBuyer;
use SimpleParkBv\Invoices\Traits\HasInvoiceDates;
use SimpleParkBv\Invoices\Traits\HasInvoiceFooter;
use SimpleParkBv\Invoices\Traits\HasInvoiceItems;
use SimpleParkBv\Invoices\Traits\HasInvoiceLanguage;
use SimpleParkBv\Invoices\Traits\HasInvoiceLogo;
use SimpleParkBv\Invoices\Traits\HasInvoiceNumber;
use SimpleParkBv\Invoices\Traits\HasInvoiceTemplate;

/**
 * Class Invoice
 */
final class Invoice
{
    use HasInvoiceBuyer;
    use HasInvoiceDates;
    use HasInvoiceFooter;
    use HasInvoiceItems;
    use HasInvoiceLanguage;
    use HasInvoiceLogo;
    use HasInvoiceNumber;
    use HasInvoiceTemplate;

    public Seller $seller;

    public mixed $pdf = null;

    public ?string $output = null;

    /**
     * @var array<string, mixed>
     */
    public array $options = [];

    /**
     * @var array<string, string>
     */
    public array $paperOptions = [];

    public function __construct()
    {
        $this->initializeHasInvoiceItems();
        $this->initializeHasInvoiceLogo();
        $this->initializeHasInvoiceLanguage();
        $this->initializeHasInvoiceDates();

        // seller (default from config)
        $this->seller = Seller::make();

        // pdf options
        $this->paperOptions = [
            'size' => config('invoices.pdf.paper_size', 'a4'),
            'orientation' => config('invoices.pdf.orientation', 'portrait'),
        ];
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * Generate the PDF instance.
     */
    public function render(): self
    {
        // save current locale
        $originalLocale = App::getLocale();

        // set locale for this invoice
        App::setLocale($this->language);

        try {
            // 'invoice' is the variable name used in the blade view
            $template = sprintf('invoices::%s', $this->template);
            $this->pdf = Pdf::loadView($template, ['invoice' => $this])
                ->setPaper($this->paperOptions['size'], $this->paperOptions['orientation']);
        } finally {
            App::setLocale($originalLocale);
        }

        return $this;
    }

    /**
     * Download the invoice as a PDF.
     */
    public function download(?string $filename = null): Response
    {
        if (! $this->pdf) {
            $this->render();
        }

        $filename = $filename ?? 'invoice-'.$this->date->format('Ymd').'.pdf';

        return $this->pdf->download($filename);
    }

    /**
     * Stream the invoice in the browser (Good for testing/previewing).
     */
    public function stream(?string $filename = null): Response
    {
        if (! $this->pdf) {
            $this->render();
        }

        $filename = $filename ?? 'invoice-'.$this->date->format('Ymd').'.pdf';

        $response = $this->pdf->stream($filename);

        // add cache-busting headers for development
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
