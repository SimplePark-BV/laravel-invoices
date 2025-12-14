<?php

namespace SimpleParkBv\LaravelInvoices;

use Illuminate\Support\ServiceProvider;

class LaravelInvoicesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // merge config if it exists
        if (file_exists(__DIR__.'/../config/invoices.php')) {
            $this->mergeConfigFrom(
                path: __DIR__.'/../config/invoices.php',
                key: 'invoices',
            );
        }
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        // publish config
        $this->publishes(
            paths: [
                __DIR__.'/../config/invoices.php' => config_path('invoices.php'),
            ],
            groups: 'invoices-config',
        );

        // load views if they exist
        if (is_dir(__DIR__.'/../resources/views')) {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'invoices');
        }

        // publish views
        $this->publishes(
            paths: [
                __DIR__.'/../resources/views' => resource_path('views/vendor/invoices'),
            ],
            groups: 'invoices-views',
        );
    }
}
