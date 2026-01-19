<?php

namespace SimpleParkBv\Invoices\Models\Traits;

/**
 * Trait HasInvoiceNumber
 *
 * @var ?string $series
 * @var int|string|null $sequence
 * @var ?string $serial
 */
trait HasInvoiceNumber
{
    protected ?string $series = null;

    protected int|string|null $sequence = null;

    protected ?string $serial = null;

    /**
     * Get the series.
     */
    public function getSeries(): ?string
    {
        return $this->series;
    }

    /**
     * Get the sequence number.
     */
    public function getSequence(): int|string|null
    {
        return $this->sequence;
    }

    /**
     * Get the serial number.
     */
    public function getSerial(): ?string
    {
        return $this->serial;
    }

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
        if (filled($this->getSerial())) {
            return $this->getSerial();
        }

        // if both series and sequence are set, combine them
        if ($this->getSeries() !== null && $this->getSequence() !== null) {
            // if sequence is numeric (int or numeric string), pad it
            if (is_numeric($this->getSequence())) {
                $paddedSequence = str_pad((string) $this->getSequence(), 8, '0', STR_PAD_LEFT);
            } else {
                // for non-numeric strings, use as-is
                $paddedSequence = (string) $this->getSequence();
            }

            return $this->getSeries().'.'.$paddedSequence;
        }

        // if only series is set, return series
        if ($this->getSeries() !== null) {
            return $this->getSeries();
        }

        // if only sequence is set, return sequence as string (padded to 8 digits if numeric)
        if ($this->getSequence() !== null) {
            if (is_numeric($this->getSequence())) {
                return str_pad((string) $this->getSequence(), 8, '0', STR_PAD_LEFT);
            }

            return (string) $this->getSequence();
        }

        // no number can be determined
        return null;
    }
}
