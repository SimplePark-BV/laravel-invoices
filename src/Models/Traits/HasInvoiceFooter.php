<?php

namespace SimpleParkBv\Invoices\Models\Traits;

/**
 * Trait HasInvoiceFooter
 */
trait HasInvoiceFooter
{
    protected ?string $footerMessage = null;

    protected ?string $conceptFooterMessage = null;

    /**
     * Set a custom footer message for issued invoices.
     *
     * Supports :amount and :date placeholders.
     *
     * @return $this
     */
    public function footerMessage(?string $message): self
    {
        $this->footerMessage = $message;

        return $this;
    }

    /**
     * Set a custom footer message for concept invoices.
     *
     * @return $this
     */
    public function conceptFooterMessage(?string $message): self
    {
        $this->conceptFooterMessage = $message;

        return $this;
    }

    /**
     * Get the custom footer message for issued invoices.
     */
    public function getCustomFooterMessage(): ?string
    {
        return $this->footerMessage;
    }

    /**
     * Get the custom footer message for concept invoices.
     */
    public function getCustomConceptFooterMessage(): ?string
    {
        return $this->conceptFooterMessage;
    }

    /**
     * Get the payment request message with formatted amount and date.
     *
     * If the invoice is not yet issued, returns a concept/draft message instead.
     */
    public function getFooterMessage(): string
    {
        // if invoice is not issued, show concept message
        if (! $this->isIssued()) {
            if ($this->conceptFooterMessage !== null) {
                return $this->conceptFooterMessage;
            }

            return __('invoices::invoice.concept_message');
        }

        /** @var string $message */
        $message = $this->footerMessage ?? __('invoices::invoice.payment_request');
        
        $amountHtml = '<span class="invoice__footer-amount">'.e($this->getFormattedTotal()).'</span>';
        $dateHtml = '<span class="invoice__footer-date">'.e($this->getFormattedDueDate()).'</span>';

        /** @var string $result */
        $result = str_replace([':amount', ':date'], [$amountHtml, $dateHtml], $message);

        return $result;
    }
}
