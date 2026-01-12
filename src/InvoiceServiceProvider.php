<?php

namespace SimpleParkBv\Invoices;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class InvoiceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // merge config
        $this->mergeConfigFrom(
            path: __DIR__.'/../config/invoices.php',
            key: 'invoices',
        );
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // share css path and font config with views using view composer
        // using view composer for better isolation and testability
        View::composer('invoices::*', function ($view): void {
            $fontDir = __DIR__.'/../resources/fonts';
            $fontPath = realpath($fontDir) ?: $fontDir;
            $cssPath = realpath(__DIR__.'/../resources/css/invoice.css') ?: __DIR__.'/../resources/css/invoice.css';
            
            // Try base64 data URIs first (most reliable for embedded fonts)
            // Fallback to file paths if base64 fails
            $fontDataUris = [];
            $fontFilePaths = [];
            $fontFiles = [
                'AvenirNext-Medium' => ['file' => 'AvenirNext-Medium.ttf', 'weight' => '400 500', 'style' => 'normal'],
                'AvenirNext-MediumItalic' => ['file' => 'AvenirNext-MediumItalic.ttf', 'weight' => '400 500', 'style' => 'italic'],
                'AvenirNext-DemiBold' => ['file' => 'AvenirNext-DemiBold.ttf', 'weight' => '600 700', 'style' => 'normal'],
                'AvenirNext-DemiBoldItalic' => ['file' => 'AvenirNext-DemiBoldItalic.ttf', 'weight' => '600 700', 'style' => 'italic'],
            ];
            
            foreach ($fontFiles as $key => $font) {
                $fontFilePath = $fontPath.'/'.$font['file'];
                $realFontPath = realpath($fontFilePath);
                
                if ($realFontPath && file_exists($realFontPath)) {
                    // Store absolute file path for file:// protocol fallback
                    $fontFilePaths[$key] = [
                        'path' => str_replace('\\', '/', $realFontPath),
                        'weight' => $font['weight'],
                        'style' => $font['style'],
                    ];
                    
                    // Try to load as base64 data URI
                    $fontData = @file_get_contents($realFontPath);
                    if ($fontData !== false && strlen($fontData) > 0) {
                        // Use font/truetype MIME type which DomPDF recognizes
                        $fontDataUris[$key] = [
                            'data' => 'data:font/truetype;base64,'.base64_encode($fontData),
                            'weight' => $font['weight'],
                            'style' => $font['style'],
                        ];
                    }
                }
            }
            
            $view->with('invoiceCssPath', $cssPath);
            $view->with('invoiceFont', config('invoices.pdf.font', 'AvenirNext'));
            $view->with('invoiceFontDataUris', $fontDataUris);
            $view->with('invoiceFontFilePaths', $fontFilePaths);
            $view->with('invoiceFontFile', config('invoices.pdf.font_file'));
        });

        // publish config
        $this->publishes(
            paths: [
                __DIR__.'/../config/invoices.php' => config_path('invoices.php'),
            ],
            groups: 'invoices-config',
        );

        // load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'invoices');

        // publish views
        $this->publishes(
            paths: [
                __DIR__.'/../resources/views' => resource_path('views/vendor/invoices'),
            ],
            groups: 'invoices-views',
        );

        // publish css
        $this->publishes(
            paths: [
                __DIR__.'/../resources/css' => resource_path('css/vendor/invoices'),
            ],
            groups: 'invoices-assets',
        );

        // publish fonts
        $this->publishes(
            paths: [
                __DIR__.'/../resources/fonts' => resource_path('fonts/vendor/invoices'),
            ],
            groups: 'invoices-assets',
        );

        // load translations
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'invoices');

        // publish translations
        $this->publishes(
            paths: [
                __DIR__.'/../resources/lang' => lang_path('vendor/invoices'),
            ],
            groups: 'invoices-lang',
        );
    }
}
