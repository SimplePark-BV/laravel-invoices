<?php

namespace SimpleParkBv\Invoices\Models\Traits;

/**
 * Trait HasInvoiceFooter
 */
trait HasInvoiceFooter
{
    /**
     * Get the payment request message with formatted amount and date.
     *
     * If the invoice is not yet issued, returns a concept/draft message instead.
     */
    public function getFooterMessage(): string
    {
        // if invoice is not issued, show concept message
        if (! $this->isIssued()) {
            return __('invoices::invoice.concept_message');
        }

        /** @var string $message */
        $message = __('invoices::invoice.payment_request');
        $amountHtml = '<span class="invoice__footer-amount">'.e($this->getFormattedTotal()).'</span>';
        $dateHtml = '<span class="invoice__footer-date">'.e($this->getFormattedDueDate()).'</span>';

        /** @var string $result */
        $result = str_replace([':amount', ':date'], [$amountHtml, $dateHtml], $message);

        return $result;
    }
}
