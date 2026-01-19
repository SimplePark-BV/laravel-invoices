<?php

namespace SimpleParkBv\Invoices\Models\Traits;

/**
 * Trait HasReceiptIds
 *
 * @var string|null $documentId
 * @var string|null $userId
 */
trait HasReceiptIds
{
    protected ?string $documentId = null;

    protected ?string $userId = null;

    /**
     * Get the document ID.
     */
    public function getDocumentId(): ?string
    {
        return $this->documentId ?? null;
    }

    /**
     * Get the user ID.
     */
    public function getUserId(): ?string
    {
        return $this->userId ?? null;
    }

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
     * Set the user ID.
     *
     * @return $this
     */
    public function userId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
