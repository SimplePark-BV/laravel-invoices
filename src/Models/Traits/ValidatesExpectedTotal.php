<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use Illuminate\Support\Facades\Log;
use SimpleParkBv\Invoices\Contracts\InvoiceInterface;
use SimpleParkBv\Invoices\Contracts\UsageReceiptInterface;
use SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException;
use SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException;

/**
 * Trait ValidatesExpectedTotal
 *
 * Provides validation for expected totals, logging errors and optionally throwing exceptions
 * when the expected total differs from the calculated total.
 */
trait ValidatesExpectedTotal
{
    /**
     * Validate that the expected total matches the calculated total.
     * Logs an error if they differ, and optionally throws an exception.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidInvoiceException
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidUsageReceiptException
     */
    protected function validateExpectedTotal(): void
    {
        // @phpstan-ignore-next-line function.alreadyNarrowedType
        if (! method_exists($this, 'getExpectedTotal') || ! method_exists($this, 'getTotal')) {
            return;
        }

        $expectedTotal = $this->getExpectedTotal();
        if ($expectedTotal === null) {
            return;
        }

        $actualTotal = $this->getTotal();
        // use tolerance of 0.01 to account for rounding differences
        if (abs($expectedTotal - $actualTotal) <= 0.01) {
            return;
        }

        $context = [];
        $errorMessage = 'Expected total differs from calculated total';

        // @phpstan-ignore-next-line function.alreadyNarrowedType,function.impossibleType
        if (method_exists($this, 'getNumber')) {
            $context['invoice_number'] = $this->getNumber();
            $errorMessage .= sprintf(' (Invoice: %s)', $this->getNumber() ?? 'N/A');
        }

        // @phpstan-ignore-next-line function.alreadyNarrowedType,function.impossibleType
        if (method_exists($this, 'getDocumentId')) {
            $context['document_id'] = $this->getDocumentId();
            $errorMessage .= sprintf(' (Document ID: %s)', $this->getDocumentId() ?? 'N/A');
        }

        // @phpstan-ignore-next-line function.alreadyNarrowedType
        if (method_exists($this, 'getDate')) {
            $date = $this->getDate();
            if ($date !== null) {
                $context['date'] = $date->format('Y-m-d');
            }
        }

        $logData = [
            'expected_total' => $expectedTotal,
            'actual_total' => $actualTotal,
            'difference' => abs($expectedTotal - $actualTotal),
            ...$context,
        ];

        // always log the error
        Log::error('Expected total differs from calculated total', $logData);

        // throw exception if configured to do so
        // @phpstan-ignore-next-line function.alreadyNarrowedType
        if (method_exists($this, 'shouldThrowOnExpectedTotalMismatch') && $this->shouldThrowOnExpectedTotalMismatch()) {
            $errorMessage .= sprintf(': expected %.2f, got %.2f (difference: %.2f)', $expectedTotal, $actualTotal, abs($expectedTotal - $actualTotal));

            // determine which exception to throw based on the class type
            if ($this instanceof InvoiceInterface) {
                throw new InvalidInvoiceException($errorMessage);
                // @phpstan-ignore-next-line instanceof.alwaysFalse
            } elseif ($this instanceof UsageReceiptInterface) {
                throw new InvalidUsageReceiptException($errorMessage);
            } else {
                // fallback: should not happen, but log and throw generic exception
                throw new InvalidInvoiceException($errorMessage);
            }
        }
    }
}
