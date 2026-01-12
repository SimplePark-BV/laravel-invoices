<!DOCTYPE html>
<html lang="{{ $invoice->language }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('invoices::invoice.invoice') }} {{ $invoice->getNumber() ?? __('invoices::invoice.concept') }}</title>
    <style>
        :root {
            --invoice-font: {{ $invoiceFont }};
        }
        @font-face {
            font-family: 'AvenirNext';
            src: url('file://{{ str_replace('\\', '/', $invoiceFontPath) }}/AvenirNext-Medium.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'AvenirNext';
            src: url('file://{{ str_replace('\\', '/', $invoiceFontPath) }}/AvenirNext-MediumItalic.ttf') format('truetype');
            font-weight: 400;
            font-style: italic;
        }
        @font-face {
            font-family: 'AvenirNext';
            src: url('file://{{ str_replace('\\', '/', $invoiceFontPath) }}/AvenirNext-DemiBold.ttf') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @font-face {
            font-family: 'AvenirNext';
            src: url('file://{{ str_replace('\\', '/', $invoiceFontPath) }}/AvenirNext-DemiBoldItalic.ttf') format('truetype');
            font-weight: 700;
            font-style: italic;
        }
        {!! file_get_contents($invoiceCssPath) !!}
    </style>
</head>
<body class="invoice">
    @include('invoices::invoice.header')
    
    @include('invoices::invoice.buyer')
    
    @include('invoices::invoice.invoice-number')
    
    @include('invoices::invoice.items')
    
    @include('invoices::invoice.footer')
</body>
</html>