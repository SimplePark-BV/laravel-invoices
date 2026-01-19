<?php

namespace SimpleParkBv\Invoices\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use RuntimeException;
use SimpleParkBv\Invoices\Contracts\InvoiceInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Models\Traits\HasBuyer;
use SimpleParkBv\Invoices\Models\Traits\HasDates;
use SimpleParkBv\Invoices\Models\Traits\HasInvoiceFooter;
use SimpleParkBv\Invoices\Models\Traits\HasInvoiceItems;
use SimpleParkBv\Invoices\Models\Traits\HasInvoiceNumber;
use SimpleParkBv\Invoices\Models\Traits\HasLanguage;
use SimpleParkBv\Invoices\Models\Traits\HasLogo;
use SimpleParkBv\Invoices\Models\Traits\HasTemplate;

/**
 * Class Invoice
 */
final class Invoice implements InvoiceInterface
{
    use CanFillFromArray;
    use HasBuyer;
    use HasDates;
    use HasInvoiceFooter;
    use HasInvoiceItems;
    use HasInvoiceNumber;
    use HasLanguage;
    use HasLogo;
    use HasTemplate;

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
        $this->initializeHasLogo();
        $this->initializeHasLanguage();
        $this->initializeHasDates();

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
            $invoice->buyer(Buyer::make()->fill($data['buyer']));
        }

        // set items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            $items = array_map(
                static fn (array $itemData) => InvoiceItem::fromArray($itemData),
                $data['items']
            );

            $invoice->items($items);
        }

        // fill remaining properties (date, serial, series, sequence, language, forced_total)
        $invoice->fill($data);

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
            'date' => $this->date?->toIso8601String(),
            'items' => $this->items->map(static fn (InvoiceItem $item) => $item->toArray())->toArray(),
            'serial' => $this->serial,
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

            // get the package root directory to allow dompdf to access fonts
            $packageRoot = realpath(__DIR__.'/../../');

            if ($packageRoot === false) {
                throw new RuntimeException(
                    'Failed to resolve package root directory. The path '.__DIR__.'/../../ could not be resolved to a valid directory.'
                );
            }

            $this->pdf = Pdf::setOptions([
                'chroot' => $packageRoot,
                'isRemoteEnabled' => false,
            ])
                ->loadView($template, ['invoice' => $this])
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

        $filename = $filename ?? 'invoice-'.($this->date?->format('Ymd') ?? 'concept').'.pdf';

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

        $filename = $filename ?? 'invoice-'.($this->date?->format('Ymd') ?? 'concept').'.pdf';

        $response = $this->pdf->stream($filename);

        // add cache-busting headers for development
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
