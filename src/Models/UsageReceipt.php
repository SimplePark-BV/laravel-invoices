<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models;

use Illuminate\Support\Collection;
use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;
use SimpleParkBv\Invoices\Services\TaxCalculator;
use SimpleParkBv\Invoices\Support\Dates;
use SimpleParkBv\Invoices\Support\Settings;
use SimpleParkBv\Invoices\Support\Text;

/**
 * A usage receipt: a confirmation of the sessions a buyer used, such as parking sessions. It is not a payment request.
 *
 * Without lines, the total is the sum of the session prices. With lines (a cost breakdown such as fees and
 * discounts, including tax), the total is the sum of the lines, and the tax they include is shown per rate.
 */
final class UsageReceipt extends Document
{
    /**
     * @var list<\SimpleParkBv\Invoices\Models\UsageReceiptItem>
     */
    private array $items = [];

    /**
     * @var list<\SimpleParkBv\Invoices\Models\UsageReceiptLine>
     */
    private array $lines = [];

    private ?string $documentId = null;

    private ?string $userId = null;

    private ?string $title = null;

    private ?string $note = null;

    private string $dateTimeFormat;

    /**
     * @throws \SimpleParkBv\Invoices\Exceptions\UnsupportedLanguageException when the default language has no translations
     */
    public function __construct()
    {
        parent::__construct();

        $this->dateTimeFormat = Settings::string('invoices.datetime_format', 'd-m-Y H:i');
    }

    /**
     * Set the sessions, replacing the current ones.
     *
     * @param  iterable<\SimpleParkBv\Invoices\Models\UsageReceiptItem|array<string, mixed>>  $items
     * @return $this
     */
    public function items(iterable $items): static
    {
        $this->items = [];

        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    /**
     * Add a session, from a UsageReceiptItem or an array of item fields.
     *
     * @param  \SimpleParkBv\Invoices\Models\UsageReceiptItem|array<string, mixed>  $item
     * @return $this
     */
    public function addItem(UsageReceiptItem|array $item): static
    {
        $this->items[] = is_array($item) ? UsageReceiptItem::make($item) : $item;

        return $this;
    }

    /**
     * @return \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Models\UsageReceiptItem>
     */
    public function getItems(): Collection
    {
        return collect($this->items);
    }

    /**
     * Set the lines of the cost breakdown, replacing the current ones.
     *
     * @param  iterable<\SimpleParkBv\Invoices\Models\UsageReceiptLine|array<string, mixed>>  $lines
     * @return $this
     */
    public function lines(iterable $lines): static
    {
        $this->lines = [];

        foreach ($lines as $line) {
            $this->addLine($line);
        }

        return $this;
    }

    /**
     * Add a line to the cost breakdown, from a UsageReceiptLine or an array of line fields.
     *
     * @param  \SimpleParkBv\Invoices\Models\UsageReceiptLine|array<string, mixed>  $line
     * @return $this
     */
    public function addLine(UsageReceiptLine|array $line): static
    {
        $this->lines[] = is_array($line) ? UsageReceiptLine::make($line) : $line;

        return $this;
    }

    /**
     * @return \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Models\UsageReceiptLine>
     */
    public function getLines(): Collection
    {
        return collect($this->lines);
    }

    /**
     * Set the id of the receipt, shown on it and used in the file name.
     *
     * @return $this
     */
    public function documentId(?string $documentId): static
    {
        $this->documentId = Text::clean($documentId);

        return $this;
    }

    /**
     * Set the id of the user the receipt is for.
     *
     * @return $this
     */
    public function userId(?string $userId): static
    {
        $this->userId = Text::clean($userId);

        return $this;
    }

    /**
     * Set the title, instead of the one from the translations.
     *
     * @return $this
     */
    public function title(?string $title): static
    {
        $this->title = Text::clean($title);

        return $this;
    }

    /**
     * Set a note, shown below the sessions. Line breaks are kept.
     *
     * @return $this
     */
    public function note(?string $note): static
    {
        $this->note = Text::clean($note);

        return $this;
    }

    /**
     * Set the format the start and end of the sessions are shown in, a PHP date format such as "d-m-Y H:i".
     *
     * @return $this
     */
    public function dateTimeFormat(string $dateTimeFormat): static
    {
        $this->dateTimeFormat = $dateTimeFormat;

        return $this;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * Get the title set with title(), or else the title from the translations, in the document language.
     */
    public function getTitle(): string
    {
        return $this->title ?? $this->translate('usage-receipt.title');
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getDateTimeFormat(): string
    {
        return $this->dateTimeFormat;
    }

    /**
     * Get the totals: of the lines when the receipt has a cost breakdown, of the session prices otherwise.
     */
    public function getTotals(): Totals
    {
        return TaxCalculator::calculate($this->lines !== [] ? $this->lines : $this->items);
    }

    /**
     * Get the default file name, e.g. "gebruiksbevestiging-PS-123.pdf", or with the date when it has no document id.
     */
    public function getFilename(): string
    {
        $suffix = $this->documentId
            ?? $this->date?->setTimezone(Dates::displayTimezone())->format('Y-m-d-H-i-s');

        $base = $this->translate('usage-receipt.filename');

        return Text::filename($suffix === null ? "{$base}.pdf" : "{$base}-{$suffix}.pdf");
    }

    public function describe(): string
    {
        return 'usage receipt'.($this->documentId === null ? '' : " {$this->documentId}");
    }

    public function getViewVariableName(): string
    {
        return 'usageReceipt';
    }

    /**
     * @return list<string>
     */
    public function getStylesheets(): array
    {
        return ['document.css', 'usage-receipt.css'];
    }

    /**
     * Convert the receipt to an array that make() accepts.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->documentToArray(),
            'items' => array_map(static fn (UsageReceiptItem $item): array => $item->toArray(), $this->items),
            'lines' => array_map(static fn (UsageReceiptLine $line): array => $line->toArray(), $this->lines),
            'document_id' => $this->documentId,
            'user_id' => $this->userId,
            'title' => $this->title,
            'note' => $this->note,
            'date_time_format' => $this->dateTimeFormat,
        ];
    }

    protected function defaultTemplate(): string
    {
        return 'invoices::usage-receipt.index';
    }

    protected function requiredTranslation(): string
    {
        return 'invoices::usage-receipt.title';
    }

    /**
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException
     */
    protected function validateDocument(): void
    {
        // the buyer name is optional on a receipt, it is not a payment request
        if ($this->buyer === null) {
            throw new InvalidUsageReceiptException('A usage receipt must have a buyer');
        }

        if ($this->seller->getName() === null) {
            throw new InvalidUsageReceiptException('The seller must have a name, set it in the invoices.seller.name config');
        }

        if ($this->items === []) {
            throw new InvalidUsageReceiptException('A usage receipt must have at least one item');
        }

        foreach ($this->items as $index => $item) {
            $item->validate($index);
        }

        foreach ($this->lines as $index => $line) {
            $line->validate($index);
        }
    }

    /**
     * @return array<string, string>
     */
    protected static function fillable(): array
    {
        return [
            ...parent::fillable(),
            'items' => 'items',
            'lines' => 'lines',
            'document_id' => 'documentId',
            'user_id' => 'userId',
            'title' => 'title',
            'note' => 'note',
            'date_time_format' => 'dateTimeFormat',
        ];
    }

    /**
     * @return class-string<\SimpleParkBv\Invoices\Exceptions\InvalidDocumentException>
     */
    protected static function invalidDataException(): string
    {
        return InvalidUsageReceiptException::class;
    }
}
