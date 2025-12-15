<?php

namespace SimpleParkBv\Invoices\Traits;

use Illuminate\Support\Collection;
use SimpleParkBv\Invoices\InvoiceItem;

/**
 * Trait HasInvoiceItems
 */
trait HasInvoiceItems
{
    /**
     * @var \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\InvoiceItem>
     */
    public Collection $items;

    public function initializeHasInvoiceItems(): void
    {
        $this->items = collect();
    }

    /**
     * Add a single item to the invoice.
     */
    public function addItem(InvoiceItem $item): self
    {
        $this->items->push($item);

        return $this;
    }

    /**
     * Add multiple items to the invoice.
     *
     * @param  array<int, \SimpleParkBv\Invoices\InvoiceItem>  $items
     */
    public function addItems(array $items): self
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    /**
     * Calculate the total tax amount.
     */
    public function taxAmount(): ?float
    {
        return $this->items->sum(function ($item) {
            if (! $item->tax_percentage) {
                return null;
            }

            $taxRate = ($item->tax_percentage ?? 0) / 100;

            return ($item->unit_price * $item->quantity) * $taxRate;
        });
    }

    /**
     * Calculate the subtotal (excluding tax).
     */
    public function subTotal(): float
    {
        return $this->items->sum(
            static fn (InvoiceItem $item): float => $item->unit_price * $item->quantity
        );
    }

    /**
     * Calculate the grand total (subtotal + tax).
     */
    public function total(): float
    {
        return $this->subTotal() + ($this->taxAmount() ?? 0);
    }

    /**
     * Get the formatted total amount with currency symbol.
     */
    public function formattedTotal(): string
    {
        $currencySymbol = config('invoices.currency_symbol', '€');

        return $currencySymbol.' '.number_format($this->total(), 2, ',', '.');
    }

    /**
     * Calculate the effective tax percentage based on subtotal and tax amount.
     * Returns null if tax amount is null (no tax applicable), 0 if subtotal is 0.
     */
    public function taxPercentage(): ?int
    {
        $subTotal = $this->subTotal();
        $taxAmount = $this->taxAmount();

        // if tax amount is null, tax is not applicable
        if ($taxAmount === null) {
            return null;
        }

        // if subtotal is 0, can't calculate percentage
        if ($subTotal <= 0) {
            return 0;
        }

        return (int) round(($taxAmount / $subTotal) * 100, 0);
    }

    /**
     * Get the formatted tax percentage for display.
     * Returns an empty string if tax is not applicable (null), otherwise returns translated percentage.
     */
    public function formattedTaxPercentage(): string
    {
        $taxPercentage = $this->taxPercentage();

        if ($taxPercentage === null) {
            return '';
        }

        return __('invoices::invoice.tax_percentage', ['percentage' => $taxPercentage]);
    }
}
