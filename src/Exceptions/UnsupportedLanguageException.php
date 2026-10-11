<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Exceptions;

/**
 * Thrown when a document is set to a language that has no translations.
 */
final class UnsupportedLanguageException extends InvalidDocumentException
{
    public static function forLanguage(string $language): self
    {
        return new self("Language '{$language}' is not supported: no invoices translations exist for it. Publish the translations with the 'invoices-lang' tag and add a lang/vendor/invoices/{$language} directory.");
    }
}
