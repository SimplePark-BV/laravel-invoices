<?php

namespace SimpleParkBv\Invoices\Traits;

/**
 * Trait HasReceiptIds
 *
 * @var string|null $documentId
 * @var string|null $userId
 */
trait HasReceiptIds
{
    public ?string $documentId = null;

    public ?string $userId = null;

    /**
     * Set the document ID.
     *
     * @return $this
     */
    public function documentId(?string $documentId): self
    {
        $this->documentId = $documentId;

        return $this;
    }

    /**
     * Get the document ID.
     */
    public function getDocumentId(): ?string
    {
        return $this->documentId ?? null;
    }

    /**
     * Set the user ID.
     *
     * @return $this
     */
    public function userId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get the user ID.
     */
    public function getUserId(): ?string
    {
        return $this->userId ?? null;
    }
}
