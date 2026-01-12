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
            $fontPath = realpath(__DIR__.'/../resources/fonts');
            $cssPath = realpath(__DIR__.'/../resources/css/invoice.css');
            
            $view->with('invoiceCssPath', $cssPath ?: __DIR__.'/../resources/css/invoice.css');
            $view->with('invoiceFont', config('invoices.pdf.font', 'AvenirNext'));
            // Use absolute path for fonts - DomPDF requires absolute file system paths
            $view->with('invoiceFontPath', $fontPath ?: __DIR__.'/../resources/fonts');
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
