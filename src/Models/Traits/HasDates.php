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
    protected ?Carbon $date = null;

    protected string $dateFormat;

    protected int $payUntilDays;

    public function initializeHasDates(): void
    {
        $this->date = null;
        $this->dateFormat = 'd-m-Y';
        $this->payUntilDays = config('invoices.default_payment_terms_days', 30);
    }

    /**
     * Get the issue date.
     */
    public function getDate(): ?Carbon
    {
        return $this->date;
    }

    /**
     * Get the date format.
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Get the payment terms in days.
     */
    public function getPayUntilDays(): int
    {
        return $this->payUntilDays;
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
     * Set the date format for displaying dates.
     *
     * @param  string  $format  The date format (e.g., 'd-m-Y', 'Y-m-d')
     * @return $this
     */
    public function dateFormat(string $format): self
    {
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * Set the payment terms in days.
     *
     * @param  int  $days  Number of days until payment is due
     * @return $this
     */
    public function payUntilDays(int $days): self
    {
        $this->payUntilDays = $days;

        return $this;
    }

    /**
     * Get the date formatted according to the date format.
     */
    public function getFormattedDate(): ?string
    {
        return $this->getDate()?->format($this->getDateFormat());
    }

    /**
     * Get the due date formatted according to the date format.
     */
    public function getFormattedDueDate(): ?string
    {
        return $this->getDate()?->copy()->addDays($this->getPayUntilDays())->format($this->getDateFormat());
    }

    /**
     * Check if the document has been issued (is official).
     * A document is issued when it has an issue date set.
     *
     * @return bool True if the document has been issued, false if it's a concept/draft
     */
    public function isIssued(): bool
    {
        return $this->getDate() !== null;
    }
}
