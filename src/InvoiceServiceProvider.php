<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices;

use Illuminate\Support\ServiceProvider;

class InvoiceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // merge recursively, so a published config still gets the nested keys added in later versions
        $this->replaceConfigRecursivelyFrom(__DIR__.'/../config/invoices.php', 'invoices');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'invoices');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'invoices');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/invoices.php' => config_path('invoices.php'),
        ], 'invoices-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/invoices'),
        ], 'invoices-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/invoices'),
        ], 'invoices-lang');
    }
}
