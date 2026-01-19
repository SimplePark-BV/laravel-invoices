<?php

namespace SimpleParkBv\Invoices\Models\Traits;

/**
 * Trait HasTemplate
 *
 * @var string $template
 */
trait HasTemplate
{
    protected string $template = 'invoice.index';

    /**
     * Get the template.
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

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
