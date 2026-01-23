<?php

namespace SimpleParkBv\Invoices\Models\Traits;

/**
 * Trait HasExpectedTotal
 */
trait HasExpectedTotal
{
    protected ?float $expectedTotal = null;

    protected bool $throwOnExpectedTotalMismatch = false;

    /**
     * Get the expected total amount.
     */
    public function getExpectedTotal(): ?float
    {
        return $this->expectedTotal;
    }

    /**
     * Set an expected total amount for validation purposes.
     * When the invoice/receipt is rendered, if the expected total differs from the calculated total, an error will be logged.
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
}
