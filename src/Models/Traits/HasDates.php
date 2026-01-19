<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use Illuminate\Support\Carbon;

/**
 * Trait HasDates
 *
 * @var \Illuminate\Support\Carbon|null $date The issue date (when the document is created/issued)
 * @var string $dateFormat
 * @var int $payUntilDays
 */
trait HasDates
{
    public ?Carbon $date = null;

    public string $dateFormat;

    public int $payUntilDays;

    public function initializeHasDates(): void
    {
        $this->date = null;
        $this->dateFormat = 'd-m-Y';
        $this->payUntilDays = config('invoices.default_payment_terms_days', 30);
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

    /**
     * Get the date formatted according to the date format.
     */
    public function getFormattedDate(): ?string
    {
        return $this->date?->format($this->dateFormat);
    }

    /**
     * Get the due date formatted according to the date format.
     */
    public function getFormattedDueDate(): ?string
    {
        return $this->date?->copy()->addDays($this->payUntilDays)->format($this->dateFormat);
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
}
