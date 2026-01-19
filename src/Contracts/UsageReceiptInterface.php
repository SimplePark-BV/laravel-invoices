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
     */
    public function buyer(PartyInterface $buyer): self;

    /**
     * Set all items for the usage receipt (replaces existing items).
     *
     * @param  array<int, \SimpleParkBv\Invoices\Contracts\ReceiptItemInterface>  $items
     */
    public function items(array $items): self;

    /**
     * Add a single item to the usage receipt.
     */
    public function addItem(ReceiptItemInterface $item): self;

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
     * Set the logo path for this usage receipt.
     */
    public function logo(?string $logoPath): self;

    /**
     * Set the template for this usage receipt.
     */
    public function template(string $template): self;

    /**
     * Force a specific total amount that will override the calculated total.
     */
    public function forcedTotal(float $amount): self;

    /**
     * Validate the usage receipt before rendering.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     */
    public function validate(): void;

    /**
     * Generate the PDF instance.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
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
