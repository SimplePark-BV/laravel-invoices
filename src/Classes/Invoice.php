<?php

namespace SimpleParkBv\Invoices;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class Invoice
 */
final class Invoice
{
    /**
     * @var \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\InvoiceItem>
     */
    public Collection $items;

    /**
     * @var \SimpleParkBv\Invoices\Seller
     */
    public Seller $seller;

    /**
     * @var \SimpleParkBv\Invoices\Buyer
     */
    public Buyer $buyer;

    /**
     * @var \Illuminate\Support\Carbon
     */
    public Carbon $date;

    /**
     * @var string
     */
    public string $date_format;

    /**
     * @var int
     */
    public int $pay_until_days;

    public function __construct()
    {
        // dates
        $this->date = Carbon::now();
        $this->date_format = config('invoices.date.format');
        $this->pay_until_days = config('invoices.default_payment_terms_days');

        // seller (default from config)
        $this->seller = Seller::make();

        // items collection
        $this->items = collect();

        // serial number
        // todo

        // currency
        // todo
    }

    public static function make(): self
    {
        return new self;
    }

    public function addItem(InvoiceItem $item): self
    {
        $this->items->push($item);

        return $this;
    }

    /**
     * @param  array<int, \SimpleParkBv\Invoices\InvoiceItem>  $items
     */
    public function addItems(array $items): self
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }
}
