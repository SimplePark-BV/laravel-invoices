<?php

namespace SimpleParkBv\Invoices\Traits;

/**
 * Trait HasInvoiceFooter
 */
trait HasInvoiceFooter
{
    /**
     * Get the payment request message with formatted amount and date.
     */
    public function footerMessage(): string
    {
        /** @var string $message */
        $message = __('invoices::invoice.payment_request');
        $amountHtml = '<span class="invoice__footer-amount">'.e($this->formattedTotal()).'</span>';
        $dateHtml = '<span class="invoice__footer-date">'.e($this->formattedDueDate()).'</span>';

        /** @var string $result */
        $result = str_replace([':amount', ':date'], [$amountHtml, $dateHtml], $message);

        return $result;
    }
}
