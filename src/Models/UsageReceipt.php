<?php

namespace SimpleParkBv\Invoices\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use RuntimeException;
use SimpleParkBv\Invoices\Contracts\ReceiptItemInterface;
use SimpleParkBv\Invoices\Contracts\UsageReceiptInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Models\Traits\HasBuyer;
use SimpleParkBv\Invoices\Models\Traits\HasDates;
use SimpleParkBv\Invoices\Models\Traits\HasLanguage;
use SimpleParkBv\Invoices\Models\Traits\HasLogo;
use SimpleParkBv\Invoices\Models\Traits\HasNotes;
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
    use HasReceiptIds;
    use HasReceiptItems;
    use HasTemplate;

    public Seller $seller;

    protected ?string $title = null;

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
            'buyer' => isset($this->buyer) ? $this->getBuyer()->toArray() : null,
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
     * Validate the usage receipt before rendering.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     */
    public function validate(): void
    {
        // buyer must be set
        if (! isset($this->buyer)) {
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
        // validate usage receipt before rendering
        $this->validate();

        // save current locale
        $originalLocale = App::getLocale();

        // set locale for this usage receipt
        App::setLocale($this->getLanguage());

        try {
            // 'usageReceipt' is the variable name used in the blade view
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
                ->loadView($template, ['usageReceipt' => $this])
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
     * Download the usage receipt as a PDF.
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
     * Stream the usage receipt in the browser (Good for testing/previewing).
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
