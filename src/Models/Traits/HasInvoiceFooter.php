<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use SimpleParkBv\Invoices\Services\CurrencyFormatter;

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
     * Supports :amount, :date and :number placeholders.
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
        return e($this->footerMessage);
    }

    /**
     * Get the custom footer message for concept invoices.
     */
    public function getCustomConceptFooterMessage(): ?string
    {
        return e($this->conceptFooterMessage);
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
                return e($this->conceptFooterMessage);
            }

            return __('invoices::invoice.concept_message');
        }

        $isNegativeTotal = $this->footerMessage === null && $this->getTotal() < 0;

        /** @var string $message */
        $message = e($this->footerMessage ?? ($isNegativeTotal
            ? __('invoices::invoice.credit_transfer_request')
            : __('invoices::invoice.payment_request')
        ));

        $formattedAmount = $isNegativeTotal
            ? CurrencyFormatter::format(abs($this->getTotal()))
            : $this->getFormattedTotal();

        $amountHtml = '<span class="invoice__footer-amount">'.e($formattedAmount).'</span>';
        $dateHtml = '<span class="invoice__footer-date">'.e($this->getFormattedDueDate()).'</span>';
        $numberHtml = '<span class="invoice__footer-number">'.e($this->getNumber() ?? '').'</span>';

        /** @var string $result */
        $result = str_replace(
            [':amount', ':date', ':number'],
            [$amountHtml, $dateHtml, $numberHtml],
            $message
        );

        return $result;
    }
}
