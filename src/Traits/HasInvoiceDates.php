<?php

namespace SimpleParkBv\Invoices\Traits;

use Illuminate\Support\Carbon;

/**
 * Trait HasInvoiceDates
 *
 * @var \Illuminate\Support\Carbon $date
 * @var string $date_format
 * @var int $pay_until_days
 */
trait HasInvoiceDates
{
    public Carbon $date;

    public string $date_format;

    public int $pay_until_days;

    public function initializeHasInvoiceDates(): void
    {
        $this->date = Carbon::now();
        $this->date_format = 'd-m-Y';
        $this->pay_until_days = config('invoices.default_payment_terms_days', 30);
    }

    /**
     * Get the invoice date formatted according to the invoice date format.
     */
    public function formattedDate(): string
    {
        return $this->date->format($this->date_format);
    }

    /**
     * Get the due date formatted according to the invoice date format.
     */
    public function formattedDueDate(): string
    {
        return $this->date->copy()->addDays($this->pay_until_days)->format($this->date_format);
    }

    /**
     * Set the invoice date.
     *
     * @return $this
     */
    public function date(Carbon $date): self
    {
        $this->date = $date;

        return $this;
    }
}
