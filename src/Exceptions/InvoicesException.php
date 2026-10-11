<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Exceptions;

use Throwable;

/**
 * Marker for every exception thrown by this package, so callers can catch them all at once.
 */
interface InvoicesException extends Throwable
{
    //
}
