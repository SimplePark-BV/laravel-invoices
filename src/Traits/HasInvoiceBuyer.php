<?php

namespace SimpleParkBv\Invoices\Traits;

use SimpleParkBv\Invoices\Buyer;

/**
 * Trait HasInvoiceBuyer
 *
 * @var \SimpleParkBv\Invoices\Buyer $buyer
 */
trait HasInvoiceBuyer
{
    public Buyer $buyer;

    /**
     * Set the buyer for this invoice.
     *
     * @return $this
     */
    public function buyer(Buyer $buyer): self
    {
        $this->buyer = $buyer;

        return $this;
    }
}
