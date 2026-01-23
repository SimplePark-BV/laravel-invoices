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
    /**
     * @var \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Contracts\UsageReceiptItemInterface>
     */
    protected Collection $items;

    protected ?float $expectedTotal = null;

    protected bool $throwOnExpectedTotalMismatch = false;

    public function initializeHasUsageReceiptItems(): void
    {
        $this->items = collect();
    }

    /**
     * Get the expected total amount.
     */
    public function getExpectedTotal(): ?float
    {
        return $this->expectedTotal;
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
     * Set an expected total amount for validation purposes.
     * When the usage receipt is rendered, if the expected total differs from the calculated total, an error will be logged.
     * Set $throw to true to throw an exception instead of just logging.
     *
     * @param  float  $amount  The expected total amount
     * @param  bool  $throw  Whether to throw an exception on mismatch (default: false)
     */
    public function expectedTotal(float $amount, bool $throw = false): self
    {
        $this->expectedTotal = $amount;
        $this->throwOnExpectedTotalMismatch = $throw;

        return $this;
    }

    /**
     * Check if exceptions should be thrown on expected total mismatch.
     */
    public function shouldThrowOnExpectedTotalMismatch(): bool
    {
        return $this->throwOnExpectedTotalMismatch;
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
