<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Services;

use Barryvdh\DomPDF\PDF;
use Closure;
use Dompdf\Dompdf;
use SimpleParkBv\Invoices\Exceptions\RenderException;
use SimpleParkBv\Invoices\Models\Document;
use SimpleParkBv\Invoices\Support\Settings;
use Throwable;

/**
 * Renders documents to HTML and PDF.
 *
 * Every call renders from scratch, so a PDF always reflects the current state of its document. The app's
 * dompdf config is kept: the package only adds its own resources to the chroot and turns off remote
 * resources, PHP and JavaScript.
 *
 * @internal
 */
final class PdfRenderer
{
    /**
     * @var array<string, string>
     */
    private static array $stylesheets = [];

    /**
     * Validate a document and render it to HTML, in the document language.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the document is invalid
     * @throws \SimpleParkBv\Invoices\Exceptions\RenderException when the view could not be rendered
     */
    public function html(Document $document): string
    {
        return $this->renderHtml($document, FontResolver::customFontFile());
    }

    /**
     * Validate a document and render it to a PDF.
     *
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the document is invalid
     * @throws \SimpleParkBv\Invoices\Exceptions\RenderException when the PDF could not be rendered
     */
    public function pdf(Document $document): PDF
    {
        $fontFile = FontResolver::customFontFile();
        $html = $this->renderHtml($document, $fontFile);

        try {
            /** @var \Barryvdh\DomPDF\PDF $pdf */
            $pdf = app('dompdf.wrapper');

            $this->configure($pdf, $fontFile);
            $this->registerFonts($pdf->getDomPDF(), $fontFile);

            $pdf->setPaper(
                Settings::string('invoices.pdf.paper_size', 'a4'),
                Settings::string('invoices.pdf.orientation', 'portrait'),
            );
            $pdf->loadHTML($html);
            $pdf->render();

            return $pdf;
        } catch (Throwable $e) {
            throw RenderException::fromThrowable($document->describe(), $e);
        }
    }

    /**
     * @throws \SimpleParkBv\Invoices\Exceptions\InvalidDocumentException when the document is invalid
     * @throws \SimpleParkBv\Invoices\Exceptions\RenderException when the view could not be rendered
     */
    private function renderHtml(Document $document, ?string $fontFile): string
    {
        // the views ask for the totals many times, so calculate them once
        return $document->withFixedTotals(function () use ($document, $fontFile): string {
            $document->validate();

            /** @var view-string $template */
            $template = $document->getTemplate();

            try {
                return $this->withLocale(
                    $document->getLanguage(),
                    fn (): string => view($template, $this->viewData($document, $fontFile))->render(),
                );
            } catch (Throwable $e) {
                throw RenderException::fromThrowable($document->describe(), $e);
            }
        });
    }

    /**
     * Get the data the views of a document receive.
     *
     * @return array<string, mixed>
     */
    private function viewData(Document $document, ?string $fontFile): array
    {
        return [
            'document' => $document,
            $document->getViewVariableName() => $document,
            'totals' => $document->getTotals(),
            'format' => $document->getFormatter(),
            'logo' => LogoResolver::dataUri($document->getLogo()),
            'fontFamily' => FontResolver::family($fontFile),
            'fontFaces' => FontResolver::fontFaces($fontFile),
            'styles' => implode("\n", array_map(self::stylesheet(...), $document->getStylesheets())),
        ];
    }

    /**
     * Configure dompdf on top of the app's dompdf config, without replacing it.
     *
     * Replacing the options would make dompdf register the fonts again on every render, outside the font
     * cache of the app, which corrupts PDFs that are rendered at the same time.
     */
    private function configure(PDF $pdf, ?string $fontFile): void
    {
        $options = $pdf->getDomPDF()->getOptions();

        // dompdf does not create its font directories, and fails when they are missing
        foreach ([$options->getFontDir(), $options->getFontCache()] as $directory) {
            if ($directory !== '' && ! is_dir($directory)) {
                @mkdir($directory, 0755, true);
            }
        }

        $chroot = [...$options->getChroot(), self::resourcesPath()];

        if ($fontFile !== null) {
            $chroot[] = dirname($fontFile);
        }

        $options->setChroot(array_values(array_unique($chroot)));
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
    }

    /**
     * Register the fonts of the documents in the font cache of dompdf, one process at a time.
     *
     * dompdf writes a font into its cache the first time it meets it, without a lock, so renders that start at
     * the same time on a cold cache (after a deploy) could read each other's half-written font files. With the
     * fonts registered here under a lock, rendering finds them in the cache and only reads it.
     */
    private function registerFonts(Dompdf $dompdf, ?string $fontFile): void
    {
        $faces = FontResolver::faces($fontFile);
        $lock = $faces === [] ? false : @fopen($dompdf->getOptions()->getFontDir().'/invoices-fonts.lock', 'c');

        // without a lock file, dompdf registers the fonts while rendering, as before
        if ($lock === false) {
            return;
        }

        try {
            flock($lock, LOCK_EX);

            // read the cache again, another process may have registered the fonts since dompdf read it
            $fontMetrics = $dompdf->getFontMetrics();
            $fontMetrics->loadFontFamilies();
            $fontMetrics->setFontFamilies();

            $family = FontResolver::family($fontFile);

            foreach ($faces as [$weight, $style, $path]) {
                // the same style and url dompdf takes from the @font-face rules, so it finds the cache entries
                $fontMetrics->registerFont(
                    ['family' => $family, 'weight' => $weight, 'style' => $style],
                    'file://'.(realpath($path) ?: $path),
                );
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Run a callback with the translator in the given locale, and restore its locale afterwards.
     *
     * Only the translator is switched, so the app locale and the events of changing it are left alone.
     *
     * @template TReturn
     *
     * @param  \Closure(): TReturn  $callback
     * @return TReturn
     */
    private function withLocale(string $locale, Closure $callback): mixed
    {
        $translator = app('translator');
        $previous = $translator->getLocale();

        $translator->setLocale($locale);

        try {
            return $callback();
        } finally {
            $translator->setLocale($previous);
        }
    }

    private static function stylesheet(string $name): string
    {
        if (! isset(self::$stylesheets[$name])) {
            $contents = @file_get_contents(self::resourcesPath().'/css/'.$name);

            if ($contents === false) {
                throw new RenderException("The stylesheet '{$name}' cannot be read");
            }

            self::$stylesheets[$name] = $contents;
        }

        return self::$stylesheets[$name];
    }

    private static function resourcesPath(): string
    {
        return dirname(__DIR__, 2).'/resources';
    }
}
