<?php

namespace SimpleParkBv\Invoices\Contracts;

use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Interface for usage receipt implementations.
 */
interface UsageReceiptInterface
{
    /**
     * Create a new usage receipt instance.
     *
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data = []): self;

    /**
     * Convert the usage receipt to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Set the buyer for this usage receipt.
     *
     * @param  \SimpleParkBv\Invoices\Contracts\PartyInterface|array<string, mixed>  $buyer
     */
    public function buyer(PartyInterface|array $buyer): self;

    /**
     * Set all items for the usage receipt (replaces existing items).
     *
     * @param  array<int, \SimpleParkBv\Invoices\Contracts\UsageReceiptItemInterface>  $items
     */
    public function items(array $items): self;

    /**
     * Add a single item to the usage receipt.
     */
    public function addItem(UsageReceiptItemInterface $item): self;

    /**
     * Set the usage receipt issue date.
     */
    public function date(Carbon|string|null $date): self;

    /**
     * Set the language for this usage receipt.
     */
    public function language(string $language): self;

    /**
     * Set the document ID for this usage receipt.
     */
    public function documentId(?string $documentId): self;

    /**
     * Set the user ID for this usage receipt.
     */
    public function userId(?string $userId): self;

    /**
     * Set the note for this usage receipt.
     */
    public function note(?string $note): self;

    /**
     * Set the title for this usage receipt.
     */
    public function title(?string $title): self;

    /**
     * Get the title for this usage receipt.
     */
    public function getTitle(): string;

    /**
     * Get the default filename for the usage receipt.
     */
    public function getFilename(): string;

    /**
     * Set the logo path for this usage receipt.
     */
    public function logo(?string $logoPath): self;

    /**
     * Set the template for this usage receipt.
     */
    public function template(string $template): self;

    /**
     * Set an expected total amount for validation purposes.
     * When the usage receipt is rendered, if the expected total differs from the calculated total, an error will be logged.
     * Set $throw to true to throw an exception instead of just logging.
     *
     * @param  float  $amount  The expected total amount
     * @param  bool  $throw  Whether to throw an exception on mismatch (default: false)
     */
    public function expectedTotal(float $amount, bool $throw = false): self;

    /**
     * Check if exceptions should be thrown on expected total mismatch.
     */
    public function shouldThrowOnExpectedTotalMismatch(): bool;

    /**
     * Validate the usage receipt before rendering.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException
     */
    public function validate(): void;

    /**
     * Generate the PDF instance.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException
     */
    public function render(): self;

    /**
     * Download the usage receipt as a PDF.
     */
    public function download(?string $filename = null): Response;

    /**
     * Stream the usage receipt in the browser.
     */
    public function stream(?string $filename = null): Response;

    /**
     * Check if the PDF has been rendered.
     */
    public function isRendered(): bool;

    /**
     * Clear the PDF instance to free memory.
     */
    public function clearPdf(): self;

    /**
     * Calculate the grand total.
     */
    public function getTotal(): float;

    /**
     * Get the formatted total amount with currency symbol.
     */
    public function getFormattedTotal(): string;

    /**
     * Get the usage receipt date formatted according to the date format.
     */
    public function getFormattedDate(): ?string;
}
