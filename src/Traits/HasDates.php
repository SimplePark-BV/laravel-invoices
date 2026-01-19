<?php

namespace SimpleParkBv\Invoices\Traits;

use Illuminate\Support\Carbon;

/**
 * Trait HasDates
 *
 * @var \Illuminate\Support\Carbon|null $date The issue date (when the document is created/issued)
 * @var string $date_format
 * @var int $pay_until_days
 */
trait HasDates
{
    public ?Carbon $date = null;

    public string $date_format;

    public int $pay_until_days;

    public function initializeHasDates(): void
    {
        $this->date = null;
        $this->date_format = 'd-m-Y';
        $this->pay_until_days = config('invoices.default_payment_terms_days', 30);
    }

    /**
     * Get the date formatted according to the date format.
     */
    public function getFormattedDate(): ?string
    {
        return $this->date?->format($this->date_format);
    }

    /**
     * Get the due date formatted according to the date format.
     */
    public function getFormattedDueDate(): ?string
    {
        return $this->date?->copy()->addDays($this->pay_until_days)->format($this->date_format);
    }

    /**
     * Check if the document has been issued (is official).
     * A document is issued when it has an issue date set.
     *
     * @return bool True if the document has been issued, false if it's a concept/draft
     */
    public function isIssued(): bool
    {
        return $this->date !== null;
    }

    /**
     * Set the issue date (when the document is created/issued).
     *
     * @param  Carbon|string|null  $date  The issue date (null to remove the date)
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
