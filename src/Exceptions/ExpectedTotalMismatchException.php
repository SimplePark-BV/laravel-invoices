<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Exceptions;

/**
 * Thrown when the total of a document differs from the total it was expected to have.
 */
final class ExpectedTotalMismatchException extends InvalidDocumentException
{
    public function __construct(
        public readonly int $expectedCents,
        public readonly int $actualCents,
        string $document,
    ) {
        parent::__construct(sprintf(
            'The total of %s is %s, but %s was expected (difference: %s)',
            $document,
            self::euros($actualCents),
            self::euros($expectedCents),
            self::euros($actualCents - $expectedCents),
        ));
    }

    private static function euros(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
