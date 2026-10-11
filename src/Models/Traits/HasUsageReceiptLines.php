<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use Illuminate\Support\Collection;
use SimpleParkBv\Invoices\Models\UsageReceiptLine;
use SimpleParkBv\Invoices\Services\TaxCalculator;

/**
 * Trait HasUsageReceiptLines
 *
 * The cost breakdown of a usage receipt. When it has lines, they make up the total instead of the
 * prices of the items.
 */
trait HasUsageReceiptLines
{
    /**
     * @var \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Models\UsageReceiptLine>
     */
    protected Collection $lines;

    public function initializeHasUsageReceiptLines(): void
    {
        $this->lines = collect();
    }

    /**
     * Get all lines.
     *
     * @return \Illuminate\Support\Collection<int, \SimpleParkBv\Invoices\Models\UsageReceiptLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    /**
     * Add a single line to the breakdown.
     *
     * @return $this
     */
    public function addLine(UsageReceiptLine $line): self
    {
        $this->lines->push($line);

        return $this;
    }

    /**
     * Set all lines of the breakdown (replaces existing lines).
     *
     * @param  array<int, \SimpleParkBv\Invoices\Models\UsageReceiptLine|array<string, mixed>>  $lines
     * @return $this
     */
    public function lines(array $lines): self
    {
        $this->lines = collect($lines)->map(
            static fn (UsageReceiptLine|array $line): UsageReceiptLine => $line instanceof UsageReceiptLine ? $line : UsageReceiptLine::make($line)
        )->values();

        return $this;
    }

    /**
     * Get the sum of all lines.
     */
    public function getLinesTotal(): float
    {
        return round($this->getLines()->sum(
            static fn (UsageReceiptLine $line): float => $line->getAmount() ?? 0.0
        ), 2);
    }

    /**
     * Get all unique tax percentages of the lines, excluding null values.
     *
     * @return \Illuminate\Support\Collection<int, float>
     */
    public function getLinesTaxGroups(): Collection
    {
        return TaxCalculator::extractTaxGroups($this->lines);
    }

    /**
     * Calculate the tax amount of the lines with a specific tax percentage.
     */
    public function getLinesTaxAmountForTaxGroup(float $taxPercentage): float
    {
        return TaxCalculator::calculateTaxForGroup($this->lines, $taxPercentage);
    }

    /**
     * Get the tax included in the lines.
     *
     * The sum of the tax groups, each rounded to cents, so it matches the tax on an invoice of the same lines.
     */
    public function getLinesTaxAmount(): float
    {
        return round($this->getLinesTaxGroups()
            ->sum(fn (float $taxPercentage): float => round($this->getLinesTaxAmountForTaxGroup($taxPercentage), 2)), 2);
    }
}
