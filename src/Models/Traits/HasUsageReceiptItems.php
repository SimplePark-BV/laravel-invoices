<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use Illuminate\Support\Collection;
use SimpleParkBv\Invoices\Contracts\UsageReceiptItemInterface;
use SimpleParkBv\Invoices\Services\CurrencyFormatter;

/**
 * Trait HasUsageReceiptItems
 */
trait HasUsageReceiptItems
{
    use HasExpectedTotal;

    /**
     * @var \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Contracts\UsageReceiptItemInterface>
     */
    protected Collection $items;

    public function initializeHasUsageReceiptItems(): void
    {
        $this->items = collect();
    }

    /**
     * Get all items.
     *
     * @return \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Contracts\UsageReceiptItemInterface>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Add a single item to the receipt.
     */
    public function addItem(UsageReceiptItemInterface $item): self
    {
        $this->items->push($item);

        return $this;
    }

    /**
     * Set all items for the receipt (replaces existing items).
     *
     * @param  array<int, \SimpleParkBv\Invoices\Contracts\UsageReceiptItemInterface>  $items
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
            static fn (UsageReceiptItemInterface $item): float => $item->getPrice() ?? 0.0
        );
    }

    /**
     * Calculate the grand total.
     *
     * Returns the sum of all session prices.
     * This ensures accuracy to the cent.
     */
    public function getTotal(): float
    {
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
