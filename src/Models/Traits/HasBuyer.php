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
    protected Buyer $buyer;

    /**
     * Set the buyer.
     *
     * @param  \SimpleParkBv\Invoices\Contracts\PartyInterface|array<string, mixed>  $buyer
     * @return $this
     */
    public function buyer(PartyInterface|array $buyer): self
    {
        // auto-cast array to Buyer instance
        if (is_array($buyer)) {
            $buyer = Buyer::make($buyer);
        }

        // for backwards compatibility, we accept any PartyInterface
        // but if it's not a Buyer instance, we create one with the same data
        if (! $buyer instanceof Buyer) {
            $buyer = Buyer::fromParty($buyer);
        }

        $this->buyer = $buyer;

        return $this;
    }

    /**
     * Get the buyer.
     */
    public function getBuyer(): Buyer
    {
        return $this->buyer;
    }
}
