<!DOCTYPE html>
<html lang="{{ $usageReceipt->getLanguage() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $usageReceipt->getTitle() }}</title>
    <style>
        :root {
            --usage-receipt-font: {{ $usageReceiptFont }};
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('file://{{ str_replace('\\', '/', $usageReceiptFontPath) }}/Montserrat-Regular.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('file://{{ str_replace('\\', '/', $usageReceiptFontPath) }}/Montserrat-Italic.ttf') format('truetype');
            font-weight: 400;
            font-style: italic;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('file://{{ str_replace('\\', '/', $usageReceiptFontPath) }}/Montserrat-SemiBold.ttf') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('file://{{ str_replace('\\', '/', $usageReceiptFontPath) }}/Montserrat-SemiBoldItalic.ttf') format('truetype');
            font-weight: 700;
            font-style: italic;
        }
        @if(is_readable($usageReceiptCssPath))
            {!! file_get_contents($usageReceiptCssPath) !!}
        @endif
    </style>
</head>
<body class="usage-receipt">
    @include('invoices::usage-receipt.header')

    @include('invoices::usage-receipt.title')

    @include('invoices::usage-receipt.buyer')

    @include('invoices::usage-receipt.ids')

    @include('invoices::usage-receipt.sessions')

    @include('invoices::usage-receipt.notes')

    @include('invoices::usage-receipt.footer')
</body>
</html>
