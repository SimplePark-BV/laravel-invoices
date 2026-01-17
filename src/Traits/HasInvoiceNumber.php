<?php

namespace SimpleParkBv\Invoices\Traits;

/**
 * Trait HasInvoiceNumber
 *
 * @var ?string $series
 * @var int|string|null $sequence
 * @var ?string $serial
 */
trait HasInvoiceNumber
{
    public ?string $series = null;

    public int|string|null $sequence = null;

    public ?string $serial = null;

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
    public function sequence(int|string|null $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Set the serial number for this invoice.
     *
     * @return $this
     */
    public function serial(?string $serial): self
    {
        $this->serial = $serial;

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
        // if serial is explicitly set, return it
        if (filled($this->serial)) {
            return $this->serial;
        }

        // if both series and sequence are set, combine them
        if ($this->series !== null && $this->sequence !== null) {
            // if sequence is numeric (int or numeric string), pad it
            if (is_numeric($this->sequence)) {
                $paddedSequence = str_pad((string) $this->sequence, 8, '0', STR_PAD_LEFT);
            } else {
                // for non-numeric strings, use as-is
                $paddedSequence = (string) $this->sequence;
            }

            return $this->series.'.'.$paddedSequence;
        }

        // if only series is set, return series
        if ($this->series !== null) {
            return $this->series;
        }

        // if only sequence is set, return sequence as string (padded to 8 digits if numeric)
        if ($this->sequence !== null) {
            if (is_numeric($this->sequence)) {
                return str_pad((string) $this->sequence, 8, '0', STR_PAD_LEFT);
            }

            return (string) $this->sequence;
        }

        // no number can be determined
        return null;
    }
}
