<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models;

use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Illuminate\Http\Response;
use SimpleParkBv\Invoices\Exceptions\ExpectedTotalMismatchException;
use SimpleParkBv\Invoices\Exceptions\InvalidDocumentException;
use SimpleParkBv\Invoices\Exceptions\UnsupportedLanguageException;
use SimpleParkBv\Invoices\Models\Traits\CanFillFromArray;
use SimpleParkBv\Invoices\Services\Formatter;
use SimpleParkBv\Invoices\Services\PdfRenderer;
use SimpleParkBv\Invoices\Support\Dates;
use SimpleParkBv\Invoices\Support\Money;
use SimpleParkBv\Invoices\Support\Settings;
use SimpleParkBv\Invoices\Support\Text;

/**
 * A document that renders to a PDF: an invoice or a usage receipt.
 *
 * Documents are built with fluent setters or from an array, and render a fresh PDF on every output call,
 * so changes made after an earlier download are never lost.
 *
 * @phpstan-consistent-constructor
 */
abstract class Document
{
    use CanFillFromArray;

    protected ?Buyer $buyer = null;

    protected Seller $seller;

    protected string $language;

    protected ?CarbonImmutable $date = null;

    protected string $dateFormat;

    protected ?string $logo;

    protected string $template;

    protected ?int $expectedTotalCents = null;

    /**
     * The totals while the document renders, so they are calculated once per render.
     */
    private ?Totals $renderTotals = null;

    private ?Formatter $formatter = null;

    /**
     * @throws \SimpleParkBv\Invoices\Exceptions\UnsupportedLanguageException when the default language has no translations
     */
    public function __construct()
    {
        $this->seller = Seller::fromConfig();
        $this->language(Settings::string('invoices.default_language', 'nl'));
        $this->dateFormat = Settings::string('invoices.date_format', 'd-m-Y');
        $this->logo = Settings::nullableString('invoices.logo');
        $this->template = $this->defaultTemplate();
    }

    /**
     * Calculate the totals of the document.
     */
    abstract protected function calculateTotals(): Totals;

    /**
     * Get the default file name of the PDF.
     */
    abstract public function getFilename(): string;

    /**
     * Get the title of the document, in the document language.
     */
    abstract public function getTitle(): string;

    /**
     * Describe the document in exception messages, e.g. "invoice 2024.0001".
     */
    abstract public function describe(): string;

    /**
     * Get the name of the view variable that holds the document, e.g. "invoice".
     */
    abstract public function getViewVariableName(): string;

    /**
     * Get the stylesheets of the document, relative to the package's resources/css directory.
     *
     * @return list<string>
     */
    abstract public function getStylesheets(): array;

    /**
     * Convert the document to an array that make() accepts.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * Get the view the document renders by default.
     */
    abstract protected function defaultTemplate(): string;

    /**
     * Get the key of a translation the document language must have, e.g. "invoices::invoice.invoice".
     */
    abstract protected function requiredTranslation(): string;

    /**
     * Validate the parts of the document, except the expected total.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException
     */
    abstract protected function validateDocument(): void;

    /**
     * Set the buyer, from a Buyer or an array of buyer fields.
     *
     * @param  \SimpleParkBv\Invoices\Models\Buyer|array<string, mixed>|null  $buyer
     * @return $this
     */
    public function buyer(Buyer|array|null $buyer): static
    {
        $this->buyer = is_array($buyer) ? $this->party(static fn (): Buyer => Buyer::make($buyer)) : $buyer;

        return $this;
    }

    public function getBuyer(): ?Buyer
    {
        return $this->buyer;
    }

    /**
     * Set the seller, from a Seller or an array of seller fields. By default, the seller comes from the invoices.seller config.
     *
     * @param  \SimpleParkBv\Invoices\Models\Seller|array<string, mixed>  $seller
     * @return $this
     */
    public function seller(Seller|array $seller): static
    {
        $this->seller = is_array($seller) ? $this->party(static fn (): Seller => Seller::make($seller)) : $seller;

        return $this;
    }

    public function getSeller(): Seller
    {
        return $this->seller;
    }

    /**
     * Whether the buyer's address shows its country: not when the buyer is in the seller's country, as on a
     * domestic invoice. Codes and names are compared as the document shows them, so "NL" and "Nederland" match.
     */
    public function showsBuyerCountry(): bool
    {
        $buyerCountry = $this->getFormatter()->country($this->buyer?->getCountry());

        if ($buyerCountry === null) {
            return false;
        }

        $sellerCountry = $this->getFormatter()->country($this->seller->getCountry());

        return $sellerCountry === null || mb_strtolower($buyerCountry) !== mb_strtolower($sellerCountry);
    }

    /**
     * Set the language of the document, e.g. "nl" or "en".
     *
     * Any language with translations works, including one added by publishing the translations. A regional
     * variant without its own translations, such as "nl_BE", falls back to its language, "nl".
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\UnsupportedLanguageException when the language has no translations
     */
    public function language(string $language): static
    {
        $parts = explode('_', str_replace('-', '_', trim($language)), 2);
        $primary = strtolower($parts[0]);
        $candidates = isset($parts[1]) ? [$primary.'_'.$parts[1], $primary] : [$primary];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && app('translator')->hasForLocale($this->requiredTranslation(), $candidate)) {
                $this->language = $candidate;
                $this->formatter = null;

                return $this;
            }
        }

        throw UnsupportedLanguageException::forLanguage($language);
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set the date of the document. Strings are read in the app timezone.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the string is not a valid date
     */
    public function date(DateTimeInterface|string|null $date): static
    {
        $this->date = Dates::parse($date, 'date', static::invalidDataException());

        return $this;
    }

    public function getDate(): ?CarbonImmutable
    {
        return $this->date;
    }

    /**
     * Set the format dates are shown in, a PHP date format such as "d-m-Y" or "j F Y".
     *
     * @return $this
     */
    public function dateFormat(string $dateFormat): static
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Get the date as shown on the document, or null when it has no date.
     */
    public function getFormattedDate(): ?string
    {
        return $this->date === null ? null : $this->getFormatter()->date($this->date, $this->dateFormat);
    }

    /**
     * Set the logo: the path to a PNG, JPG, GIF or SVG file (relative paths are read from the app's base path),
     * a data URI, or null for no logo. The default comes from the invoices.logo config.
     *
     * @return $this
     */
    public function logo(?string $logo): static
    {
        $this->logo = Text::clean($logo);

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    /**
     * Set the view to render, e.g. "invoices::invoice.index" or a view of the app such as "pdf.invoice".
     *
     * @return $this
     */
    public function template(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Set the total the document must have, or null for no check.
     *
     * Validating or rendering the document throws an ExpectedTotalMismatchException when its total
     * differs by even one cent, so a document never shows another total than the one charged.
     *
     * @return $this
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the amount is not a number with at most 2 decimals
     */
    public function expectedTotal(float|int|string|null $expectedTotal): static
    {
        $this->expectedTotalCents = Money::toCents($expectedTotal, 'expected total', static::invalidDataException());

        return $this;
    }

    public function getExpectedTotal(): ?float
    {
        return $this->expectedTotalCents === null ? null : Money::toFloat($this->expectedTotalCents);
    }

    /**
     * Get the totals of the document.
     */
    public function getTotals(): Totals
    {
        return $this->renderTotals ?? $this->calculateTotals();
    }

    /**
     * Run a callback with the totals calculated once, for rendering. The document must not change meanwhile.
     *
     * @internal
     *
     * @template TReturn
     *
     * @param  \Closure(): TReturn  $callback
     * @return TReturn
     */
    public function withFixedTotals(Closure $callback): mixed
    {
        if ($this->renderTotals !== null) {
            return $callback();
        }

        $this->renderTotals = $this->calculateTotals();

        try {
            return $callback();
        } finally {
            $this->renderTotals = null;
        }
    }

    /**
     * Get the total including tax.
     */
    public function getTotal(): float
    {
        return $this->getTotals()->getTotal();
    }

    /**
     * Get the tax included in the total.
     */
    public function getTaxAmount(): float
    {
        return $this->getTotals()->getTax();
    }

    /**
     * Get the formatter for the language of the document.
     */
    public function getFormatter(): Formatter
    {
        return $this->formatter ??= new Formatter($this->language);
    }

    /**
     * Validate the document. Rendering validates too.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException
     * @throws \SimpleParkBv\Invoices\Exceptions\ExpectedTotalMismatchException
     */
    public function validate(): void
    {
        $this->validateDocument();

        $totalCents = $this->getTotals()->getTotalCents();

        if ($this->expectedTotalCents !== null && $this->expectedTotalCents !== $totalCents) {
            throw new ExpectedTotalMismatchException($this->expectedTotalCents, $totalCents, $this->describe());
        }
    }

    /**
     * Render the document to HTML.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the document is invalid
     * @throws \SimpleParkBv\Invoices\Exceptions\RenderException when the view could not be rendered
     */
    public function toHtml(): string
    {
        return app(PdfRenderer::class)->html($this);
    }

    /**
     * Render the document to the contents of a PDF file.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the document is invalid
     * @throws \SimpleParkBv\Invoices\Exceptions\RenderException when the PDF could not be rendered
     */
    public function toPdf(): string
    {
        return app(PdfRenderer::class)->pdf($this)->output();
    }

    /**
     * Render the document to a response that downloads the PDF.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the document is invalid
     * @throws \SimpleParkBv\Invoices\Exceptions\RenderException when the PDF could not be rendered
     */
    public function download(?string $filename = null): Response
    {
        return app(PdfRenderer::class)->pdf($this)->download($this->resolveFilename($filename));
    }

    /**
     * Render the document to a response that shows the PDF in the browser.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the document is invalid
     * @throws \SimpleParkBv\Invoices\Exceptions\RenderException when the PDF could not be rendered
     */
    public function stream(?string $filename = null): Response
    {
        return app(PdfRenderer::class)->pdf($this)->stream($this->resolveFilename($filename));
    }

    /**
     * Translate a key of the invoices translations in the document language.
     *
     * @param  array<string, string>  $replace
     */
    protected function translate(string $key, array $replace = []): string
    {
        $translation = __("invoices::{$key}", $replace, $this->language);

        return is_string($translation) ? $translation : $key;
    }

    /**
     * Convert the fields every document has to an array.
     *
     * @return array<string, mixed>
     */
    protected function documentToArray(): array
    {
        return [
            'buyer' => $this->buyer?->toArray(),
            'seller' => $this->seller->toArray(),
            'language' => $this->language,
            'date' => $this->date?->toIso8601String(),
            'date_format' => $this->dateFormat,
            'logo' => $this->logo,
            'template' => $this->template,
            'expected_total' => $this->getExpectedTotal(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function fillable(): array
    {
        return [
            'buyer' => 'buyer',
            'seller' => 'seller',
            'language' => 'language',
            'date' => 'date',
            'date_format' => 'dateFormat',
            'logo' => 'logo',
            'template' => 'template',
            'expected_total' => 'expectedTotal',
        ];
    }

    /**
     * Make a party, throwing the exception of this document for invalid data.
     *
     * @template TParty of \SimpleParkBv\Invoices\Models\Party
     *
     * @param  \Closure(): TParty  $make
     * @return TParty
     */
    private function party(\Closure $make): Party
    {
        try {
            return $make();
        } catch (InvalidDocumentException $e) {
            $exception = static::invalidDataException();

            throw new $exception($e->getMessage(), 0, $e);
        }
    }

    private function resolveFilename(?string $filename): string
    {
        $filename = Text::clean($filename);

        return $filename === null ? $this->getFilename() : Text::filename($filename);
    }
}
