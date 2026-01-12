<?php

namespace SimpleParkBv\Invoices\Traits;

use Illuminate\Support\Carbon;

/**
 * Trait HasInvoiceDates
 *
 * @var \Illuminate\Support\Carbon|null $date The invoice issue date (when the invoice is created/issued)
 * @var string $date_format
 * @var int $pay_until_days
 */
trait HasInvoiceDates
{
    public ?Carbon $date = null;

    public string $date_format;

    public int $pay_until_days;

    public function initializeHasInvoiceDates(): void
    {
        $this->date = null;
        $this->date_format = 'd-m-Y';
        $this->pay_until_days = config('invoices.default_payment_terms_days', 30);
    }

    /**
     * Get the invoice date formatted according to the invoice date format.
     */
    public function getFormattedDate(): ?string
    {
        return $this->date?->format($this->date_format);
    }

    /**
     * Get the due date formatted according to the invoice date format.
     */
    public function getFormattedDueDate(): ?string
    {
        return $this->date?->copy()->addDays($this->pay_until_days)->format($this->date_format);
    }

    /**
     * Check if the invoice has been issued (is official).
     * An invoice is issued when it has an issue date set.
     *
     * @return bool True if the invoice has been issued, false if it's a concept/draft
     */
    public function isIssued(): bool
    {
        return $this->date !== null;
    }

    /**
     * Set the invoice issue date (when the invoice is created/issued).
     *
     * @param  Carbon|string|null  $date  The invoice issue date (null to remove the date)
     * @return $this
     */
    public function date(Carbon|string|null $date): self
    {
        if ($date === null) {
            $this->date = null;
        } elseif (is_string($date)) {
            $this->date = Carbon::parse($date);
        } else {
            $this->date = $date;
        }

        return $this;
    }
}
