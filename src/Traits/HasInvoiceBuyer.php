<?php

namespace SimpleParkBv\Invoices\Traits;

use SimpleParkBv\Invoices\Contracts\PartyInterface;
use SimpleParkBv\Invoices\Models\Buyer;

/**
 * Trait HasInvoiceBuyer
 *
 * @var \SimpleParkBv\Invoices\Models\Buyer $buyer
 */
trait HasInvoiceBuyer
{
    public Buyer $buyer;

    /**
     * Set the buyer for this invoice.
     *
     * @return $this
     */
    public function buyer(PartyInterface $buyer): self
    {
        // For backwards compatibility, we accept any PartyInterface
        // but if it's not a Buyer instance, we create one with the same data
        if (! $buyer instanceof Buyer) {
            $newBuyer = Buyer::make();
            $newBuyer->name = $buyer->getName();
            $newBuyer->address = $buyer->getAddress();
            $newBuyer->city = $buyer->getCity();
            $newBuyer->postalCode = $buyer->getPostalCode();
            $newBuyer->country = $buyer->getCountry();
            $newBuyer->email = $buyer->getEmail();
            $newBuyer->phone = $buyer->getPhone();
            $newBuyer->website = $buyer->getWebsite();
            $buyer = $newBuyer;
        }

        $this->buyer = $buyer;

        return $this;
    }
}
