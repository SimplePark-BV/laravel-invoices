<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use SimpleParkBv\Invoices\Enums\PaymentMethod;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Services\TaxCalculator;
use SimpleParkBv\Invoices\Support\Dates;
use SimpleParkBv\Invoices\Support\Settings;
use SimpleParkBv\Invoices\Support\Text;

/**
 * An invoice. Without a date it is a concept: no payment is asked and it shows no number-based dates.
 *
 * Unit prices include tax. A negative total makes the invoice a credit invoice.
 */
final class Invoice extends Document
{
    /**
     * @var list<\SimpleParkBv\Invoices\Models\InvoiceItem>
     */
    private array $items = [];

    private ?string $serial = null;

    private ?string $series = null;

    private int|string|null $sequence = null;

    private ?CarbonImmutable $dueDate = null;

    private int $paymentTermsDays;

    private PaymentMethod $paymentMethod = PaymentMethod::BankTransfer;

    private bool $paid = false;

    private ?string $footerMessage = null;

    private ?string $conceptFooterMessage = null;

    /**
     * @throws \SimpleParkBv\Invoices\Exceptions\UnsupportedLanguageException when the default language has no translations
     */
    public function __construct()
    {
        parent::__construct();

        $this->paymentTermsDays = max(0, Settings::int('invoices.default_payment_terms_days', 30));
    }

    /**
     * Set the items, replacing the current ones.
     *
     * @param  iterable<\SimpleParkBv\Invoices\Models\InvoiceItem|array<string, mixed>>  $items
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
     * Add an item, from an InvoiceItem or an array of item fields.
     *
     * @param  \SimpleParkBv\Invoices\Models\InvoiceItem|array<string, mixed>  $item
     * @return $this
     */
    public function addItem(InvoiceItem|array $item): static
    {
        $this->items[] = is_array($item) ? InvoiceItem::make($item) : $item;

        return $this;
    }

    /**
     * @return \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Models\InvoiceItem>
     */
    public function getItems(): Collection
    {
        return collect($this->items);
    }

    /**
     * Set the complete invoice number, e.g. "2024.0001". It takes precedence over series and sequence.
     *
     * @return $this
     */
    public function serial(?string $serial): static
    {
        $this->serial = Text::clean($serial);

        return $this;
    }

    /**
     * Set the series of the invoice number, e.g. "2024", combined with the sequence into "2024.00000001".
     *
     * @return $this
     */
    public function series(?string $series): static
    {
        $this->series = Text::clean($series);

        return $this;
    }

    /**
     * Set the sequence of the invoice number. Numeric sequences are padded to 8 digits.
     *
     * @return $this
     */
    public function sequence(int|string|null $sequence): static
    {
        $this->sequence = is_string($sequence) ? Text::clean($sequence) : $sequence;

        return $this;
    }

    public function getSerial(): ?string
    {
        return $this->serial;
    }

    public function getSeries(): ?string
    {
        return $this->series;
    }

    public function getSequence(): int|string|null
    {
        return $this->sequence;
    }

    /**
     * Get the invoice number: the serial, or else the series and the padded sequence, or null when there is none.
     */
    public function getNumber(): ?string
    {
        if ($this->serial !== null) {
            return $this->serial;
        }

        $sequence = match (true) {
            $this->sequence === null => null,
            is_numeric($this->sequence) => str_pad((string) $this->sequence, 8, '0', STR_PAD_LEFT),
            default => (string) $this->sequence,
        };

        $number = implode('.', array_filter([$this->series, $sequence], static fn (?string $part): bool => $part !== null));

        return $number === '' ? null : $number;
    }

    /**
     * Set the due date. Without one, the due date is the invoice date plus the payment terms.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException when the string is not a valid date
     */
    public function dueDate(DateTimeInterface|string|null $dueDate): static
    {
        $this->dueDate = Dates::parse($dueDate, 'due date', InvalidInvoiceException::class);

        return $this;
    }

    /**
     * Get the due date: the one set, or else the invoice date plus the payment terms, or null for a concept.
     */
    public function getDueDate(): ?CarbonImmutable
    {
        // count the days in the display timezone, so a fixed offset such as +02:00 never shifts the day across DST
        return $this->dueDate ?? $this->date?->setTimezone(Dates::displayTimezone())->addDays($this->paymentTermsDays);
    }

    /**
     * Get the due date as shown on the invoice, or null when there is none.
     */
    public function getFormattedDueDate(): ?string
    {
        $dueDate = $this->getDueDate();

        return $dueDate === null ? null : $this->getFormatter()->date($dueDate, $this->dateFormat);
    }

    /**
     * Set the number of days after the invoice date the invoice is due, from invoices.default_payment_terms_days by default.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException when the number of days is negative
     */
    public function paymentTermsDays(int $days): static
    {
        if ($days < 0) {
            throw new InvalidInvoiceException("The payment terms must not be negative, {$days} days given");
        }

        $this->paymentTermsDays = $days;

        return $this;
    }

    public function getPaymentTermsDays(): int
    {
        return $this->paymentTermsDays;
    }

    /**
     * Set how the buyer pays, which decides the payment text in the footer.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException when the payment method is unknown
     */
    public function paymentMethod(PaymentMethod|string $paymentMethod): static
    {
        $this->paymentMethod = is_string($paymentMethod)
            ? PaymentMethod::tryFrom($paymentMethod) ?? throw new InvalidInvoiceException("Unknown payment method '{$paymentMethod}'")
            : $paymentMethod;

        return $this;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * Mark the invoice as paid, so the footer confirms the payment instead of asking for it.
     *
     * @return $this
     */
    public function paid(bool $paid = true): static
    {
        $this->paid = $paid;

        return $this;
    }

    public function isPaid(): bool
    {
        return $this->paid;
    }

    /**
     * Set a custom footer text for issued invoices, instead of the payment text. It is shown as plain text,
     * with :amount, :date (the due date) and :number replaced.
     *
     * @return $this
     */
    public function footerMessage(?string $message): static
    {
        $this->footerMessage = Text::clean($message);

        return $this;
    }

    /**
     * Set a custom footer text for concept invoices, shown as plain text.
     *
     * @return $this
     */
    public function conceptFooterMessage(?string $message): static
    {
        $this->conceptFooterMessage = Text::clean($message);

        return $this;
    }

    public function getCustomFooterMessage(): ?string
    {
        return $this->footerMessage;
    }

    public function getCustomConceptFooterMessage(): ?string
    {
        return $this->conceptFooterMessage;
    }

    /**
     * Check if the invoice is issued, i.e. it has a date. An invoice without a date is a concept.
     */
    public function isIssued(): bool
    {
        return $this->date !== null;
    }

    /**
     * Check if the invoice is a credit invoice, i.e. its total is negative.
     */
    public function isCredit(): bool
    {
        return $this->getTotals()->getTotalCents() < 0;
    }

    /**
     * Check if the buyer still has to pay: the invoice is issued, not paid, and its total is positive.
     */
    public function isPaymentDue(): bool
    {
        return $this->isIssued() && ! $this->paid && $this->getTotals()->getTotalCents() > 0;
    }

    public function getTotals(): Totals
    {
        return TaxCalculator::calculate($this->items);
    }

    /**
     * Get the total excluding tax.
     */
    public function getSubtotal(): float
    {
        return $this->getTotals()->getSubtotal();
    }

    /**
     * Get the footer text as HTML, in the document language.
     *
     * The text depends on the state of the invoice: a concept, a custom text, nothing to pay, a credit
     * invoice, paid, paid by direct debit, or else a request to pay by bank transfer. The texts from the
     * translations may contain HTML; custom texts are escaped. The placeholders are replaced by the amount,
     * the due date and the number.
     */
    public function getFooterMessage(): string
    {
        if (! $this->isIssued()) {
            return $this->conceptFooterMessage !== null
                ? e($this->conceptFooterMessage)
                : $this->translate('invoice.concept_message');
        }

        $totalCents = $this->getTotals()->getTotalCents();

        $message = match (true) {
            $this->footerMessage !== null => e($this->footerMessage),
            $totalCents === 0 => $this->translate('invoice.nothing_due'),
            $totalCents < 0 => $this->translate('invoice.credit_transfer_request'),
            $this->paid => $this->translate('invoice.paid'),
            $this->paymentMethod === PaymentMethod::DirectDebit => $this->translate('invoice.direct_debit'),
            default => $this->translate('invoice.payment_request'),
        };

        // the default texts speak of an amount to pay or to refund, a custom text gets the total as it is
        $amountCents = $this->footerMessage === null ? abs($totalCents) : $totalCents;

        return str_replace(
            [':amount', ':date', ':number'],
            [
                '<span class="invoice__footer-amount">'.e($this->getFormatter()->money($amountCents)).'</span>',
                '<span class="invoice__footer-date">'.e($this->getFormattedDueDate() ?? '').'</span>',
                '<span class="invoice__footer-number">'.e($this->getNumber() ?? '').'</span>',
            ],
            $message,
        );
    }

    /**
     * Get the title, e.g. "Factuur 2024.0001", "Creditfactuur 2024.0002" or "Factuur CONCEPT".
     */
    public function getTitle(): string
    {
        $type = $this->translate($this->isCredit() ? 'invoice.credit_invoice' : 'invoice.invoice');

        return $type.' '.($this->getNumber() ?? $this->translate('invoice.concept'));
    }

    /**
     * Get the default file name, e.g. "factuur-2024.0001.pdf", or "factuur-CONCEPT.pdf" without a number.
     */
    public function getFilename(): string
    {
        $number = $this->getNumber() ?? $this->translate('invoice.concept');

        return Text::filename($this->translate('invoice.filename').'-'.$number.'.pdf');
    }

    public function describe(): string
    {
        return 'invoice '.($this->getNumber() ?? '(concept)');
    }

    public function getViewVariableName(): string
    {
        return 'invoice';
    }

    /**
     * @return list<string>
     */
    public function getStylesheets(): array
    {
        return ['document.css', 'invoice.css'];
    }

    /**
     * Convert the invoice to an array that make() accepts.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->documentToArray(),
            'items' => array_map(static fn (InvoiceItem $item): array => $item->toArray(), $this->items),
            'serial' => $this->serial,
            'series' => $this->series,
            'sequence' => $this->sequence,
            'due_date' => $this->dueDate?->toIso8601String(),
            'payment_terms_days' => $this->paymentTermsDays,
            'payment_method' => $this->paymentMethod->value,
            'paid' => $this->paid,
            'footer_message' => $this->footerMessage,
            'concept_footer_message' => $this->conceptFooterMessage,
        ];
    }

    protected function defaultTemplate(): string
    {
        return 'invoices::invoice.index';
    }

    protected function requiredTranslation(): string
    {
        return 'invoices::invoice.invoice';
    }

    /**
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     */
    protected function validateDocument(): void
    {
        if ($this->buyer === null) {
            throw new InvalidInvoiceException('An invoice must have a buyer');
        }

        if ($this->buyer->getName() === null) {
            throw new InvalidInvoiceException('The buyer of an invoice must have a name');
        }

        if ($this->seller->getName() === null) {
            throw new InvalidInvoiceException('The seller must have a name, set it in the invoices.seller.name config');
        }

        if ($this->items === []) {
            throw new InvalidInvoiceException('An invoice must have at least one item');
        }

        foreach ($this->items as $index => $item) {
            $item->validate($index);
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
            'serial' => 'serial',
            'series' => 'series',
            'sequence' => 'sequence',
            'due_date' => 'dueDate',
            'payment_terms_days' => 'paymentTermsDays',
            'payment_method' => 'paymentMethod',
            'paid' => 'paid',
            'footer_message' => 'footerMessage',
            'concept_footer_message' => 'conceptFooterMessage',
        ];
    }

    /**
     * @return class-string<\SimpleParkBv\Invoices\Exceptions\InvalidDocumentException>
     */
    protected static function invalidDataException(): string
    {
        return InvalidInvoiceException::class;
    }
}
