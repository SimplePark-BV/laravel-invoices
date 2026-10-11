<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when a valid document could not be rendered to HTML or PDF.
 */
final class RenderException extends RuntimeException implements InvoicesException
{
    public static function fromThrowable(string $document, Throwable $previous): self
    {
        return new self("Failed to render {$document}: {$previous->getMessage()}", 0, $previous);
    }
}
