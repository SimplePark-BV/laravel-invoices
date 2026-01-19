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
     * Set the template.
     *
     * @return $this
     */
    public function template(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get the template.
     */
    public function getTemplate(): string
    {
        return $this->template;
    }
}
