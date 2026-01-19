<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use SimpleParkBv\Invoices\Contracts\PartyInterface;
use SimpleParkBv\Invoices\Models\Buyer;

/**
 * Trait HasBuyer
 *
 * @var \SimpleParkBv\Invoices\Models\Buyer $buyer
 */
trait HasBuyer
{
    public Buyer $buyer;

    /**
     * Set the buyer.
     *
     * @return $this
     */
    public function buyer(PartyInterface $buyer): self
    {
        // for backwards compatibility, we accept any PartyInterface
        // but if it's not a Buyer instance, we create one with the same data
        if (! $buyer instanceof Buyer) {
            $buyer = Buyer::fromParty($buyer);
        }

        $this->buyer = $buyer;

        return $this;
    }
}
