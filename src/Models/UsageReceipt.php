<?php

namespace SimpleParkBv\Invoices\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use SimpleParkBv\Invoices\Contracts\ReceiptItemInterface;
use SimpleParkBv\Invoices\Contracts\UsageReceiptInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Models\Traits\HasBuyer;
use SimpleParkBv\Invoices\Models\Traits\HasDates;
use SimpleParkBv\Invoices\Models\Traits\HasLanguage;
use SimpleParkBv\Invoices\Models\Traits\HasLogo;
use SimpleParkBv\Invoices\Models\Traits\HasNotes;
use SimpleParkBv\Invoices\Models\Traits\HasPdfRendering;
use SimpleParkBv\Invoices\Models\Traits\HasReceiptIds;
use SimpleParkBv\Invoices\Models\Traits\HasReceiptItems;
use SimpleParkBv\Invoices\Models\Traits\HasTemplate;

/**
 * Class UsageReceipt
 *
 * @property string|null $title
 */
final class UsageReceipt implements UsageReceiptInterface
{
    use CanFillFromArray;
    use HasBuyer;
    use HasDates;
    use HasLanguage;
    use HasLogo;
    use HasNotes;
    use HasPdfRendering;
    use HasReceiptIds;
    use HasReceiptItems;
    use HasTemplate;

    public Seller $seller;

    protected ?string $title = null;

    public function __construct()
    {
        $this->initializeHasReceiptItems();
        $this->initializeHasLogo();
        $this->initializeHasLanguage();
        $this->initializeHasDates();

        // seller (default from config)
        $this->seller = Seller::make();

        // default template for usage receipts
        $this->template = 'usage-receipt.index';

        // pdf options
        $this->paperOptions = [
            'size' => config('invoices.pdf.paper_size', 'a4'),
            'orientation' => config('invoices.pdf.orientation', 'portrait'),
        ];
    }

    /**
     * Create a new usage receipt instance.
     *
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data = []): self
    {
        $usageReceipt = new self;

        if (empty($data)) {
            return $usageReceipt;
        }

        // set buyer if provided
        if (isset($data['buyer']) && is_array($data['buyer'])) {
            $usageReceipt->buyer(Buyer::make($data['buyer']));
        }

        // set items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            $items = array_map(
                static fn ($item) => $item instanceof ReceiptItemInterface ? $item : ReceiptItem::make($item),
                $data['items']
            );
            $usageReceipt->items($items);
        }

        // fill remaining properties (date, document_id, user_id, title, language, note, forced_total)
        $usageReceipt->fill($data);

        return $usageReceipt;
    }

    /**
     * Set the receipt title.
     *
     * @return $this
     */
    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the receipt title.
     */
    public function getTitle(): string
    {
        return $this->title ?? __('invoices::usage-receipt.title');
    }

    /**
     * Get the seller.
     */
    public function getSeller(): Seller
    {
        return $this->seller;
    }

    /**
     * Get the default filename for the usage receipt.
     *
     * format: {translatable-base}-Y-m-d-H-i-s.pdf
     * example: gebruiksbevestiging-2026-01-19-14-30-00.pdf
     */
    public function getFilename(): string
    {
        // use the receipt's language for translation
        $base = __('invoices::usage-receipt.filename', [], $this->getLanguage());
        $datetime = $this->getDate()
            ? $this->getDate()->format('Y-m-d-H-i-s')
            : now()->format('Y-m-d-H-i-s');

        return "{$base}-{$datetime}.pdf";
    }

    /**
     * Convert the usage receipt to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'buyer' => $this->getBuyer()?->toArray() ?? null,
            'date' => $this->getDate()?->toIso8601String(),
            'items' => $this->getItems()->map(static fn (ReceiptItemInterface $item) => $item->toArray())->toArray(),
            'document_id' => $this->documentId,
            'user_id' => $this->userId,
            'title' => $this->title,
            'language' => $this->getLanguage(),
            'note' => $this->note,
            'forced_total' => $this->getForcedTotal(),
        ];
    }

    /**
     * Get the view variable name to use when rendering.
     */
    protected function getViewVariableName(): string
    {
        return 'usageReceipt';
    }

    /**
     * Validate the usage receipt before rendering.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     */
    public function validate(): void
    {
        // buyer must be set
        if ($this->getBuyer() === null) {
            throw new InvalidInvoiceException('Buyer is required for usage receipt');
        }

        // at least one item must exist
        if ($this->getItems()->isEmpty()) {
            throw new InvalidInvoiceException('Usage receipt must have at least one item');
        }

        // validate all items
        foreach ($this->getItems() as $index => $item) {
            try {
                $item->validate($index);
            } catch (\SimpleParkBv\Invoices\Exceptions\InvalidReceiptItemException $e) {
                throw new InvalidInvoiceException($e->getMessage(), 0, $e);
            }
        }
    }
}
