<?php

namespace SimpleParkBv\Invoices;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use SimpleParkBv\Invoices\Contracts\InvoiceInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
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
final class Invoice implements InvoiceInterface
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
     * Create an invoice from an array of data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $invoice = self::make();

        // set buyer if provided
        if (isset($data['buyer']) && is_array($data['buyer'])) {
            $buyer = Buyer::make();

            foreach ($data['buyer'] as $key => $value) {
                if (property_exists($buyer, $key)) {
                    $buyer->$key = $value;
                }
            }

            $invoice->buyer($buyer);
        }

        // set date if provided
        if (isset($data['date'])) {
            $invoice->date($data['date']);
        }

        // set items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            $items = [];
            foreach ($data['items'] as $itemData) {
                $item = InvoiceItem::make();

                if (isset($itemData['title'])) {
                    $item->title($itemData['title']);
                }

                if (isset($itemData['description'])) {
                    $item->description($itemData['description']);
                }

                if (isset($itemData['quantity'])) {
                    $item->quantity($itemData['quantity']);
                }

                if (isset($itemData['unit_price'])) {
                    $item->unitPrice($itemData['unit_price']);
                }

                if (isset($itemData['tax_percentage'])) {
                    $item->taxPercentage($itemData['tax_percentage']);
                }

                $items[] = $item;
            }
            $invoice->items($items);
        }

        // set invoice number if provided
        if (isset($data['series'])) {
            $invoice->series($data['series']);
        }

        if (isset($data['sequence'])) {
            $invoice->sequence($data['sequence']);
        }

        // set language if provided
        if (isset($data['language'])) {
            $invoice->language($data['language']);
        }

        // set forced total if provided
        if (isset($data['forced_total'])) {
            $invoice->forcedTotal($data['forced_total']);
        }

        return $invoice;
    }

    /**
     * Convert the invoice to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'buyer' => isset($this->buyer) ? $this->buyer->toArray() : null,
            'date' => $this->date->toIso8601String(),
            'items' => $this->items->map(static fn (InvoiceItem $item) => $item->toArray())->toArray(),
            'series' => $this->series,
            'sequence' => $this->sequence,
            'language' => $this->language,
            'forced_total' => $this->forcedTotal,
        ];
    }

    /**
     * Validate the invoice before rendering.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     */
    public function validate(): void
    {
        // buyer must be set
        if (! isset($this->buyer)) {
            throw new InvalidInvoiceException('Buyer is required for invoice');
        }

        // at least one item must exist
        if ($this->items->isEmpty()) {
            throw new InvalidInvoiceException('Invoice must have at least one item');
        }

        // validate all items
        foreach ($this->items as $index => $item) {
            try {
                $item->validate($index);
            } catch (\SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException $e) {
                throw new InvalidInvoiceException($e->getMessage(), 0, $e);
            }
        }
    }

    /**
     * Check if the PDF has been rendered.
     */
    public function isRendered(): bool
    {
        return $this->pdf !== null;
    }

    /**
     * Clear the PDF instance to free memory.
     */
    public function clearPdf(): self
    {
        $this->pdf = null;

        return $this;
    }

    /**
     * Generate the PDF instance.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     */
    public function render(): self
    {
        // validate invoice before rendering
        $this->validate();

        // save current locale
        $originalLocale = App::getLocale();

        // set locale for this invoice
        App::setLocale($this->language);

        try {
            // 'invoice' is the variable name used in the blade view
            $template = sprintf('invoices::%s', $this->template);
            $this->pdf = Pdf::loadView($template, ['invoice' => $this])
                ->setPaper($this->paperOptions['size'], $this->paperOptions['orientation'])
                ->setOption('isRemoteEnabled', true)
                ->setOption('enable-local-file-access', true);
        } catch (\Throwable $e) {
            $this->pdf = null;
            throw new InvalidInvoiceException('Failed to render PDF: '.$e->getMessage(), 0, $e);
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

        if (! $this->pdf) {
            throw new InvalidInvoiceException('Failed to render PDF');
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

        if (! $this->pdf) {
            throw new InvalidInvoiceException('Failed to render PDF');
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
