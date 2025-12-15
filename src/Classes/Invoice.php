<?php

namespace SimpleParkBv\Invoices;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use SimpleParkBv\Invoices\Traits\HasInvoiceItems;
use SimpleParkBv\Invoices\Traits\HasInvoiceLogo;
use SimpleParkBv\Invoices\Traits\HasInvoiceNumber;

/**
 * Class Invoice
 */
final class Invoice
{
    use HasInvoiceItems;
    use HasInvoiceLogo;
    use HasInvoiceNumber;

    public Seller $seller;

    public Buyer $buyer;

    public Carbon $date;

    public string $date_format;

    public int $pay_until_days;

    public string $language;

    public string $template = 'invoice.index';

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

        // dates
        $this->date = Carbon::now();
        $this->date_format = 'd-m-Y';
        $this->pay_until_days = config('invoices.default_payment_terms_days', 30);

        // language (default from config)
        $this->language = config('invoices.default_language', 'nl');

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
     * Set the language for this invoice.
     *
     * @return $this
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get the invoice date formatted according to the invoice date format.
     */
    public function formattedDate(): string
    {
        return $this->date->format($this->date_format);
    }

    /**
     * Get the due date formatted according to the invoice date format.
     */
    public function formattedDueDate(): string
    {
        return $this->date->copy()->addDays($this->pay_until_days)->format($this->date_format);
    }

    /**
     * Get the payment request message with formatted amount and date.
     */
    public function paymentRequestMessage(): string
    {
        /** @var string $message */
        $message = __('invoices::invoice.payment_request');
        $amountHtml = '<span class="invoice__footer-amount">'.e($this->formattedTotal()).'</span>';
        $dateHtml = '<span class="invoice__footer-date">'.e($this->formattedDueDate()).'</span>';

        /** @var string $result */
        $result = str_replace([':amount', ':date'], [$amountHtml, $dateHtml], $message);

        return $result;
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
