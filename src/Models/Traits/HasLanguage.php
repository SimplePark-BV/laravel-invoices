<?php

namespace SimpleParkBv\Invoices\Models\Traits;

use RuntimeException;

/**
 * Trait HasLanguage
 *
 * @var string $language
 */
trait HasLanguage
{
    protected string $language;

    /**
     * Cached list of available languages to avoid repeated filesystem scans.
     *
     * @var array<int, string>|null
     */
    private static ?array $cachedAvailableLanguages = null;

    public function initializeHasLanguage(): void
    {
        $this->language = config('invoices.default_language', 'nl');
    }

    /**
     * Get the language.
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set the language.
     *
     * @return $this
     *
     * @throws \RuntimeException
     */
    public function language(string $language): self
    {
        $availableLanguages = $this->getAvailableLanguages();

        if (! in_array($language, $availableLanguages, true)) {
            throw new RuntimeException("Language '{$language}' is not supported. Available languages: ".implode(', ', $availableLanguages));
        }

        $this->language = $language;

        return $this;
    }

    /**
     * Get available languages from translation files.
     *
     * Results are cached to avoid repeated filesystem scans. Returns an empty
     * array if the language directory cannot be read or does not exist.
     *
     * @return array<int, string>
     */
    public function getAvailableLanguages(): array
    {
        // return cached result if available
        if (self::$cachedAvailableLanguages !== null) {
            return self::$cachedAvailableLanguages;
        }

        $langPath = __DIR__.'/../../../resources/lang';
        $languages = [];

        if (is_dir($langPath)) {
            // use glob() with GLOB_ONLYDIR to get only directories, avoiding . and ..
            $directories = glob($langPath.'/*', GLOB_ONLYDIR);

            // handle glob() failure (returns false on error)
            if ($directories !== false) {
                $languages = array_map('basename', $directories);
            }
        }

        // cache the result (even if empty) to avoid repeated scans
        self::$cachedAvailableLanguages = $languages;

        return $languages;
    }

    /**
     * Clear the cached available languages.
     * Useful for testing or when languages are added at runtime.
     */
    public static function clearLanguageCache(): void
    {
        self::$cachedAvailableLanguages = null;
    }
}
