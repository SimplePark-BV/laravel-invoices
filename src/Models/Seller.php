<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Models;

use SimpleParkBv\Invoices\Support\Text;

/**
 * The party that issues a document. Documents use the seller from the invoices.seller config by default.
 */
final class Seller extends Party
{
    private ?string $registrationNumber = null;

    private ?string $taxId = null;

    private ?string $bankAccount = null;

    /**
     * Create a seller from the invoices.seller config. Keys the seller does not know are ignored.
     */
    public static function fromConfig(): self
    {
        $config = config('invoices.seller');
        $seller = new self;

        if (! is_array($config)) {
            return $seller;
        }

        foreach (self::fillable() as $key => $setter) {
            $value = $config[$key] ?? null;

            $seller->{$setter}(is_scalar($value) ? (string) $value : null);
        }

        return $seller;
    }

    /**
     * Set the chamber of commerce (KvK) registration number.
     *
     * @return $this
     */
    public function registrationNumber(?string $registrationNumber): static
    {
        $this->registrationNumber = Text::clean($registrationNumber);

        return $this;
    }

    /**
     * Set the VAT identification number.
     *
     * @return $this
     */
    public function taxId(?string $taxId): static
    {
        $this->taxId = Text::clean($taxId);

        return $this;
    }

    /**
     * Set the bank account (IBAN).
     *
     * @return $this
     */
    public function bankAccount(?string $bankAccount): static
    {
        $this->bankAccount = Text::clean($bankAccount);

        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function getBankAccount(): ?string
    {
        return $this->bankAccount;
    }

    /**
     * Convert the seller to an array that make() accepts.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'registration_number' => $this->registrationNumber,
            'tax_id' => $this->taxId,
            'bank_account' => $this->bankAccount,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function fillable(): array
    {
        return [
            ...parent::fillable(),
            'registration_number' => 'registrationNumber',
            'tax_id' => 'taxId',
            'bank_account' => 'bankAccount',
        ];
    }
}
