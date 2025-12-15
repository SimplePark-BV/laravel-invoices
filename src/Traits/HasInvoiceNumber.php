<?php

namespace SimpleParkBv\Invoices\Traits;

/**
 * Trait HasInvoiceNumber
 *
 * Provides invoice numbering functionality with series and sequence support.
 */
trait HasInvoiceNumber
{
    public ?string $series = null;

    public ?int $sequence = null;

    /**
     * Set the series for this invoice.
     *
     * @return $this
     */
    public function series(?string $series): self
    {
        $this->series = $series;

        return $this;
    }

    /**
     * Set the sequence number for this invoice.
     *
     * @return $this
     */
    public function sequence(?int $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get the invoice number, built dynamically from series and sequence if available.
     *
     * Returns the explicitly set number if present, otherwise builds from series and/or sequence.
     * Returns null if no number can be determined.
     */
    public function getNumber(): ?string
    {
        // if both series and sequence are set, combine them
        if ($this->series !== null && $this->sequence !== null) {
            return $this->series.'.'.$this->sequence;
        }

        // if only series is set, return series
        if ($this->series !== null) {
            return $this->series;
        }

        // if only sequence is set, return sequence as string
        if ($this->sequence !== null) {
            return (string) $this->sequence;
        }

        // no number can be determined
        return null;
    }
}
