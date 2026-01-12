<?php

namespace SimpleParkBv\Invoices\Traits;

/**
 * Trait HasInvoiceFooter
 */
trait HasInvoiceFooter
{
    /**
     * Get the payment request message with formatted amount and date.
     *
     * If the invoice is finalized, returns a message explaining this is a draft.
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
