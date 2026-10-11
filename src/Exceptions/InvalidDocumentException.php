<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when the data of a document, or of one of its parts, is invalid.
 */
class InvalidDocumentException extends InvalidArgumentException implements InvoicesException
{
    //
}
