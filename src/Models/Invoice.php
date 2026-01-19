<?php

namespace SimpleParkBv\Invoices\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use SimpleParkBv\Invoices\Contracts\InvoiceInterface;
use SimpleParkBv\Invoices\Contracts\InvoiceItemInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Models\Traits\HasBuyer;
use SimpleParkBv\Invoices\Models\Traits\HasDates;
use SimpleParkBv\Invoices\Models\Traits\HasInvoiceFooter;
use SimpleParkBv\Invoices\Models\Traits\HasInvoiceItems;
use SimpleParkBv\Invoices\Models\Traits\HasInvoiceNumber;
use SimpleParkBv\Invoices\Models\Traits\HasLanguage;
use SimpleParkBv\Invoices\Models\Traits\HasLogo;
use SimpleParkBv\Invoices\Models\Traits\HasPdfRendering;
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
    use HasPdfRendering;
    use HasTemplate;

    public Seller $seller;

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

    /**
     * Create a new invoice instance.
     *
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data = []): self
    {
        $invoice = new self;

        if (empty($data)) {
            return $invoice;
        }

        // set buyer if provided
        if (isset($data['buyer']) && is_array($data['buyer'])) {
            $invoice->buyer(Buyer::make($data['buyer']));
        }

        // set items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            $items = array_map(
                static fn ($item) => $item instanceof InvoiceItemInterface ? $item : InvoiceItem::make($item),
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
            'buyer' => $this->getBuyer()?->toArray() ?? null,
            'date' => $this->getDate()?->toIso8601String(),
            'items' => $this->getItems()->map(static fn (InvoiceItemInterface $item) => $item->toArray())->toArray(),
            'serial' => $this->getSerial(),
            'series' => $this->getSeries(),
            'sequence' => $this->getSequence(),
            'language' => $this->getLanguage(),
            'forced_total' => $this->getForcedTotal(),
        ];
    }

    /**
     * Get the default filename for the invoice.
     *
     * format: {translatable-base}-{date|concept}.pdf
     * example: invoice-20260119.pdf or factuur-20260119.pdf
     */
    public function getFilename(): string
    {
        // use the invoice's language for translation
        $base = __('invoices::invoice.filename', [], $this->getLanguage());
        $dateString = $this->getDate()?->format('Ymd') ?? __('invoices::invoice.concept', [], $this->getLanguage());

        return "{$base}-{$dateString}.pdf";
    }

    /**
     * Get the seller.
     */
    public function getSeller(): Seller
    {
        return $this->seller;
    }

    /**
     * Get the view variable name to use when rendering.
     */
    protected function getViewVariableName(): string
    {
        return 'invoice';
    }

    /**
     * Validate the invoice before rendering.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     */
    public function validate(): void
    {
        // buyer must be set
        if ($this->getBuyer() === null) {
            throw new InvalidInvoiceException('Buyer is required for invoice');
        }

        // at least one item must exist
        if ($this->getItems()->isEmpty()) {
            throw new InvalidInvoiceException('Invoice must have at least one item');
        }

        // validate all items
        foreach ($this->getItems() as $index => $item) {
            try {
                $item->validate($index);
            } catch (\SimpleParkBv\Invoices\Exceptions\InvalidInvoiceItemException $e) {
                throw new InvalidInvoiceException($e->getMessage(), 0, $e);
            }
        }
    }
}
