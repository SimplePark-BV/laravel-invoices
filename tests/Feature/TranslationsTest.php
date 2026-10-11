<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TranslationsTest extends TestCase
{
    private const LANG_PATH = __DIR__.'/../../resources/lang';

    #[Test]
    #[DataProvider('files')]
    public function every_language_has_the_same_keys_and_placeholders(string $file): void
    {
        // arrange
        $dutch = $this->lines('nl', $file);
        $english = $this->lines('en', $file);

        // assert
        $this->assertSame(array_keys($dutch), array_keys($english));

        foreach ($dutch as $key => $line) {
            $this->assertSame($this->placeholders($line), $this->placeholders($english[$key]), "The placeholders of {$file}.{$key} differ");
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function files(): array
    {
        return [
            'invoice' => ['invoice'],
            'usage receipt' => ['usage-receipt'],
            'format' => ['format'],
        ];
    }

    #[Test]
    public function every_language_has_the_same_files(): void
    {
        // act
        $files = static fn (string $language): array => array_map(
            static fn (\SplFileInfo $file): string => $file->getFilename(),
            File::files(self::LANG_PATH."/{$language}"),
        );

        // assert
        $this->assertSame($files('nl'), $files('en'));
    }

    #[Test]
    public function every_translation_the_package_uses_exists_in_every_language(): void
    {
        // arrange
        $keys = $this->usedKeys();

        // assert
        $this->assertNotEmpty($keys);

        foreach (['nl', 'en'] as $language) {
            foreach ($keys as $key) {
                $this->assertTrue(Lang::hasForLocale("invoices::{$key}", $language), "The translation invoices::{$key} is missing in {$language}");
            }
        }
    }

    /**
     * Get the translation keys used in the source and the views, leaving out keys that are built at runtime.
     *
     * @return list<string>
     */
    private function usedKeys(): array
    {
        $files = [
            ...File::allFiles(__DIR__.'/../../src'),
            ...File::allFiles(__DIR__.'/../../resources/views'),
        ];

        $keys = [];

        foreach ($files as $file) {
            // keys with the namespace, such as 'invoices::invoice.total', and keys passed to translate(), such as 'invoice.paid'
            preg_match_all("/'invoices::([a-z-]+\.[a-z_.]+)'|'((?:invoice|usage-receipt|format)\.[a-z_.]+)'/", $file->getContents(), $matches);

            foreach ([...$matches[1], ...$matches[2]] as $key) {
                // leave out keys built at runtime, and the names of views and stylesheets
                if ($key !== '' && ! str_ends_with($key, '.') && ! str_ends_with($key, '.css') && ! view()->exists("invoices::{$key}")) {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Get the lines of a translation file, flattened to dot notation.
     *
     * @return array<string, string>
     */
    private function lines(string $language, string $file): array
    {
        $lines = require self::LANG_PATH."/{$language}/{$file}.php";
        $this->assertIsArray($lines);

        /** @var array<string, string> $flattened */
        $flattened = Arr::dot($lines);

        return $flattened;
    }

    /**
     * @return list<string>
     */
    private function placeholders(string $line): array
    {
        preg_match_all('/:[a-z_]+/', $line, $matches);

        $placeholders = array_values(array_unique($matches[0]));
        sort($placeholders);

        return $placeholders;
    }
}
