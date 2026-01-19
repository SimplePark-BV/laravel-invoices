<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use Illuminate\Support\Collection;
use SimpleParkBv\Invoices\Contracts\ReceiptItemInterface;
use SimpleParkBv\Invoices\Services\CurrencyFormatter;

/**
 * Trait HasReceiptItems
 */
trait HasReceiptItems
{
    /**
     * @var \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Contracts\ReceiptItemInterface>
     */
    protected Collection $items;

    protected ?float $forcedTotal = null;

    public function initializeHasReceiptItems(): void
    {
        $this->items = collect();
        $this->forcedTotal = null;
    }

    /**
     * Get the forced total amount.
     */
    public function getForcedTotal(): ?float
    {
        return $this->forcedTotal;
    }

    /**
     * Get all items.
     *
     * @return \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Contracts\ReceiptItemInterface>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Add a single item to the receipt.
     */
    public function addItem(ReceiptItemInterface $item): self
    {
        $this->items->push($item);

        return $this;
    }

    /**
     * Force a specific total amount that will override the calculated total.
     * Useful when you need to ensure the total matches a specific amount (e.g., from external systems).
     */
    public function forcedTotal(float $amount): self
    {
        $this->forcedTotal = $amount;

        return $this;
    }

    /**
     * Set all items for the receipt (replaces existing items).
     *
     * @param  array<int, \SimpleParkBv\Invoices\Contracts\ReceiptItemInterface>  $items
     */
    public function items(array $items): self
    {
        $this->items = collect($items);

        return $this;
    }

    /**
     * Get the sum of all parking session prices.
     */
    public function getItemsTotal(): float
    {
        return $this->getItems()->sum(
            static fn (ReceiptItemInterface $item): float => $item->getPrice() ?? 0.0
        );
    }

    /**
     * Calculate the grand total.
     *
     * Returns the forced total if set via forcedTotal(), otherwise returns the sum of all session prices.
     *
     * WARNING: Do not use this method for calculations when forcedTotal() is set,
     * as the returned amount may differ from the calculated sum of items.
     * Use getItemsTotal() for calculations that need to match the actual sum of all items.
     */
    public function getTotal(): float
    {
        if ($this->getForcedTotal() !== null) {
            return $this->getForcedTotal();
        }

        // always calculate from items directly to ensure precision to the cent
        return $this->getItemsTotal();
    }

    /**
     * Get the formatted total amount with currency symbol.
     */
    public function getFormattedTotal(): string
    {
        return CurrencyFormatter::format($this->getTotal());
    }
}
