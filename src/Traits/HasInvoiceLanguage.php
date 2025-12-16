<?php

namespace SimpleParkBv\Invoices\Traits;

use RuntimeException;

/**
 * Trait HasInvoiceLanguage
 *
 * @var string $language
 */
trait HasInvoiceLanguage
{
    public string $language;

    public function initializeHasInvoiceLanguage(): void
    {
        $this->language = config('invoices.default_language', 'nl');
    }

    /**
     * Get available languages from translation files.
     *
     * @return array<int, string>
     */
    public function getAvailableLanguages(): array
    {
        $langPath = __DIR__.'/../../resources/lang';
        $languages = [];

        if (is_dir($langPath)) {
            $directories = scandir($langPath);
            foreach ($directories as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($langPath.'/'.$dir)) {
                    $languages[] = $dir;
                }
            }
        }

        return $languages;
    }

    /**
     * Set the language for this invoice.
     *
     * @return $this
     *
     * @throws \RuntimeException
     */
    public function setLanguage(string $language): self
    {
        $availableLanguages = $this->getAvailableLanguages();

        if (! in_array($language, $availableLanguages, true)) {
            throw new RuntimeException("Language '{$language}' is not supported. Available languages: ".implode(', ', $availableLanguages));
        }

        $this->language = $language;

        return $this;
    }
}
