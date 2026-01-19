<?php

namespace SimpleParkBv\Invoices\Contracts;

use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface for invoice implementations.
 */
interface InvoiceInterface
{
    /**
     * Create a new invoice instance.
     *
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data = []): self;

    /**
     * Convert the invoice to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Set the buyer for this invoice.
     *
     * @param  \SimpleParkBv\Invoices\Contracts\PartyInterface|array<string, mixed>  $buyer
     */
    public function buyer(PartyInterface|array $buyer): self;

    /**
     * Set all items for the invoice (replaces existing items).
     *
     * @param  array<int, \SimpleParkBv\Invoices\Contracts\InvoiceItemInterface>  $items
     */
    public function items(array $items): self;

    /**
     * Add a single item to the invoice.
     */
    public function addItem(InvoiceItemInterface $item): self;

    /**
     * Set the invoice issue date.
     */
    public function date(Carbon|string|null $date): self;

    /**
     * Set the language for this invoice.
     */
    public function language(string $language): self;

    /**
     * Set the series for this invoice.
     */
    public function series(?string $series): self;

    /**
     * Set the sequence number for this invoice.
     */
    public function sequence(int|string|null $sequence): self;

    /**
     * Set the serial number for this invoice.
     */
    public function serial(?string $serial): self;

    /**
     * Set the logo path for this invoice.
     */
    public function logo(?string $logoPath): self;

    /**
     * Set the template for this invoice.
     */
    public function template(string $template): self;

    /**
     * Force a specific total amount that will override the calculated total.
     */
    public function forcedTotal(float $amount): self;

    /**
     * Validate the invoice before rendering.
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
     * Download the invoice as a PDF.
     */
    public function download(?string $filename = null): Response;

    /**
     * Stream the invoice in the browser.
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
     * Get the sum of all items (unit_price * quantity for all items).
     */
    public function getItemsTotal(): float;

    /**
     * Calculate the subtotal (total excluding tax).
     */
    public function getSubTotal(): float;

    /**
     * Calculate the total tax amount.
     */
    public function getTaxAmount(): float;

    /**
     * Calculate the grand total.
     */
    public function getTotal(): float;

    /**
     * Get the formatted total amount with currency symbol.
     */
    public function getFormattedTotal(): string;

    /**
     * Get the invoice number.
     */
    public function getNumber(): ?string;

    /**
     * Get the invoice date formatted according to the invoice date format.
     */
    public function getFormattedDate(): ?string;

    /**
     * Get the due date formatted according to the invoice date format.
     */
    public function getFormattedDueDate(): ?string;

    /**
     * Get all unique tax percentages from items.
     *
     * @return Collection<int, float>
     */
    public function getTaxGroups(): Collection;

    /**
     * Calculate the tax amount for items with a specific tax percentage.
     */
    public function getTaxAmountForTaxGroup(float $taxPercentage): float;

    /**
     * Check if the invoice has been issued (is official).
     */
    public function isIssued(): bool;
}
