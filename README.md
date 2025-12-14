# laravel-invoices

This Laravel package provides an easy to use interface to generate Invoice PDF files with your provided data.

## Installation

You can install the package via Composer:

```bash
composer require simplepark-bv/laravel-invoices
```

The service provider will be automatically registered via Laravel's package auto-discovery.

### Publish Configuration

Publish the configuration file to customize the package settings:

```bash
php artisan vendor:publish --provider="SimpleParkBv\LaravelInvoices\LaravelInvoicesServiceProvider" --tag="invoices-config"
```

This will create a `config/invoices.php` file where you can customize:

- Currency settings
- Default tax rate
- Payment terms
- PDF generation settings

## Development

This package uses Laravel Sail for local development and Orchestra Testbench for testing.

### Initial Setup

1. Install dependencies:
```bash
composer install
```

2. Start Sail:
```bash
./vendor/bin/sail up -d
```

### Running Tests

```bash
./vendor/bin/sail test
```
