<!DOCTYPE html>
<html lang="{{ $invoice->getLanguage() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('invoices::invoice.invoice') }} {{ $invoice->getNumber() ?? __('invoices::invoice.concept') }}</title>
    <style>
        :root {
            --invoice-font: {{ $invoiceFont }};
        }
        @font-face {
            font-family: 'Poppins';
            src: url('file://{{ str_replace('\\', '/', $invoiceFontPath) }}/Poppins-Regular.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Poppins';
            src: url('file://{{ str_replace('\\', '/', $invoiceFontPath) }}/Poppins-Italic.ttf') format('truetype');
            font-weight: 400;
            font-style: italic;
        }
        @font-face {
            font-family: 'Poppins';
            src: url('file://{{ str_replace('\\', '/', $invoiceFontPath) }}/Poppins-Medium.ttf') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @font-face {
            font-family: 'Poppins';
            src: url('file://{{ str_replace('\\', '/', $invoiceFontPath) }}/Poppins-MediumItalic.ttf') format('truetype');
            font-weight: 700;
            font-style: italic;
        }
        @if(is_readable($invoiceCssPath))
            {!! file_get_contents($invoiceCssPath) !!}
        @endif
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