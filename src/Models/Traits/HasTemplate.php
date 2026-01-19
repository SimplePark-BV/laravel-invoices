<?php

namespace SimpleParkBv\Invoices\Models\Traits;

/**
 * Trait HasTemplate
 *
 * @var string $template
 */
trait HasTemplate
{
    public string $template = 'invoice.index';

    /**
     * Set the template.
     *
     * @return $this
     */
    public function template(string $template): self
    {
        $this->template = $template;

        return $this;
    }
}
