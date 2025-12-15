<?php

namespace SimpleParkBv\Invoices\Traits;

/**
 * Trait HasInvoiceLanguage
 * 
 * @var string $language
 */
trait HasInvoiceLanguage
{
    public string $language;

    public function initializeHasInvoiceLanguage(): void
    {
        $this->language = config('invoices.default_language', 'nl');
    }

    /**
     * Set the language for this invoice.
     *
     * @return $this
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }
}

