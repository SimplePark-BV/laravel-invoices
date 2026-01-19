<?php

/**
 * Usage Receipt Render Script
 *
 * This script renders the usage receipt template and saves it as a PDF.
 *
 * Usage:
 *   php render-usage-receipt.php
 */

require __DIR__.'/vendor/autoload.php';

use Barryvdh\DomPDF\ServiceProvider as DomPDFServiceProvider;
use Orchestra\Testbench\TestCase;
use SimpleParkBv\Invoices\InvoiceServiceProvider;

/**
 * Minimal test case to bootstrap Laravel for PDF rendering
 */
class UsageReceiptRenderer extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DomPDFServiceProvider::class,
            InvoiceServiceProvider::class,
        ];
    }

    public function execute(): void
    {
        // bootstrap the application
        $this->setUp();

        $this->renderPdf();

        echo "Done!\n";

        // exit immediately to avoid tearDown issues when running outside PHPUnit
        exit(0);
    }

    protected function renderPdf(): void
    {
        // create a usage receipt with sample data
        $usageReceipt = \SimpleParkBv\Invoices\Models\UsageReceipt::make();

        // set buyer
        $buyer = \SimpleParkBv\Invoices\Models\Buyer::make();
        $buyer->name = 'Lucas Castelein';
        $buyer->address = 'Beuningenstraat 10';
        $buyer->postalCode = '5043 XM';
        $buyer->city = 'Tilburg';
        $buyer->email = 'castelein.lucas@gmail.com';
        $buyer->phone = '+31 6 82926450';
        $usageReceipt->buyer($buyer);

        // set date
        $usageReceipt->date('2025-09-25');

        // set IDs
        $usageReceipt->documentId('1234567891');
        $usageReceipt->userId('1234567891');

        // set note
        $usageReceipt->note('Dit is een notitie die ik toegevoegd heb als gebruiker!');

        // add parking sessions
        $sessions = [];
        for ($i = 0; $i < 20; $i++) {
            $session = \SimpleParkBv\Invoices\Models\ReceiptItem::make();
            $session->user('Jasper Demmers');
            $session->identifier('GJJ73R');
            $session->startDate('2025-10-22 19:44:00');
            $session->endDate('2025-10-22 20:44:00');
            $session->category('5132');
            $session->price(12.37);
            $sessions[] = $session;
        }
        $usageReceipt->items($sessions);

        // set language
        $usageReceipt->language('nl');

        // render and save the PDF
        $outputPath = __DIR__.'/parkeerbevestiging-25-09-2025.pdf';
        file_put_contents($outputPath, $usageReceipt->render()->pdf->output());

        echo "PDF saved to: {$outputPath}\n";
    }
}

// create renderer and execute
$renderer = new UsageReceiptRenderer('execute');
$renderer->execute();
