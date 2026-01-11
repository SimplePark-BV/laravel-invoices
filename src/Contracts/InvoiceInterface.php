<?php

namespace SimpleParkBv\Invoices\Contracts;

use Illuminate\Http\Response;

/**
 * Interface for invoice implementations.
 */
interface InvoiceInterface
{
    /**
     * Generate the PDF instance.
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
     * Validate the invoice.
     */
    public function validate(): void;
}
