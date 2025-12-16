<?php

namespace SimpleParkBv\Invoices\Traits;

/**
 * Trait HasInvoiceTemplate
 *
 * @var string $template
 */
trait HasInvoiceTemplate
{
    public string $template = 'invoice.index';

    /**
     * Set the template for this invoice.
     *
     * @return $this
     */
    public function template(string $template): self
    {
        $this->template = $template;

        return $this;
    }
}
