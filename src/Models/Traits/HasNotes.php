<?php

namespace SimpleParkBv\Invoices\Models\Traits;

/**
 * Trait HasNotes
 *
 * @var string|null $note
 */
trait HasNotes
{
    protected ?string $note = null;

    /**
     * Get the note.
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * Set the note.
     *
     * @return $this
     */
    public function note(?string $note): self
    {
        $this->note = $note;

        return $this;
    }
}
