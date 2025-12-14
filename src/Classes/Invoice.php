<?php

namespace SimpleParkBv\Invoices;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Class Invoice
 */
final class Invoice
{
    /**
     * @var \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\InvoiceItem>
     */
    public Collection $items;

    public Seller $seller;

    public Buyer $buyer;

    public Carbon $date;

    public string $date_format;

    public int $pay_until_days;

    public string $template = 'invoice.index';

    public string|null $logo = null;

    public mixed $pdf = null;

    public string|null $output = null;

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
        // dates
        $this->date = Carbon::now();
        $this->date_format = 'd-m-Y';
        $this->pay_until_days = config('invoices.default_payment_terms_days', 30);

        // seller (default from config)
        $this->seller = Seller::make();

        // logo (default from config)
        $this->logo = config('invoices.logo');

        // items collection
        $this->items = collect();

        // pdf options
        $this->paperOptions = [
            'size' => config('invoices.pdf.paper_size', 'a4'),
            'orientation' => config('invoices.pdf.orientation', 'portrait'),
        ];

        // serial number
        // todo

        // currency
        // todo
    }

    public static function make(): self
    {
        return new self;
    }

    public function addItem(InvoiceItem $item): self
    {
        $this->items->push($item);

        return $this;
    }

    /**
     * @param  array<int, \SimpleParkBv\Invoices\InvoiceItem>  $items
     */
    public function addItems(array $items): self
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    /**
     * Set the logo path for this invoice.
     *
     * @param  string|null  $logoPath
     * @return $this
     */
    public function setLogo(?string $logoPath): self
    {
        $this->logo = $logoPath;

        return $this;
    }

    /**
     * Get the logo as a data URI for PDF rendering.
     * Supports PNG, JPG, JPEG, GIF, and SVG formats.
     *
     * Note: SVG support in dompdf is limited and may render incorrectly.
     * PNG is recommended for best compatibility.
     *
     * @return string|null
     */
    public function getLogoDataUri(): ?string
    {
        if (! $this->logo || ! file_exists($this->logo)) {
            return null;
        }
        
        $imageData = file_get_contents($this->logo);
        $imageInfo = getimagesize($this->logo);
        
        if ($imageInfo === false) {
            return null;
        }

        $mimeType = $imageInfo['mime'];
        $base64 = base64_encode($imageData);

        return sprintf('data:%s;base64,%s', $mimeType, $base64);
    }

    /**
     * Calculate the subtotal (excluding tax).
     */
    public function subTotal(): float
    {
        return $this->items->sum(
            static fn (InvoiceItem $item): float => $item->unit_price * $item->quantity
        );
    }

    /**
     * Calculate the total tax amount.
     */
    public function taxAmount(): float|null
    {
        return $this->items->sum(function ($item) {
            if (! $item->tax_percentage) {
                return null;
            }

            $taxRate = ($item->tax_percentage ?? 0) / 100;
            return ($item->unit_price * $item->quantity) * $taxRate;
        });
    }

    /**
     * Calculate the grand total (subtotal + tax).
     */
    public function total(): float
    {
        return $this->subTotal() + $this->taxAmount() ?? 0;
    }

    /**
     * Generate the PDF instance.
     */
    public function render(): self
    {
        // 'invoice' is the variable name used in the blade view
        $template = sprintf('invoices::%s', $this->template);
        $this->pdf = Pdf::loadView($template, ['invoice' => $this])
            ->setPaper($this->paperOptions['size'], $this->paperOptions['orientation']);

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

        $filename = $filename ?? 'invoice-' . $this->date->format('Ymd') . '.pdf';

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

        $filename = $filename ?? 'invoice-' . $this->date->format('Ymd') . '.pdf';

        return $this->pdf->stream($filename);
    }
}
