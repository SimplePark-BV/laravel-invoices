<!DOCTYPE html>
<html lang="{{ $invoice->language }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('invoices::invoice.invoice') }} {{ $invoice->getNumber() ?? __('invoices::invoice.concept') }}</title>
    <style>
        :root {
            --invoice-font: {{ $invoiceFont }};
        }
        @php
            // Prefer base64 data URIs, fallback to file:// protocol
            $useDataUris = isset($invoiceFontDataUris) && !empty($invoiceFontDataUris);
            $fontSources = $useDataUris ? $invoiceFontDataUris : ($invoiceFontFilePaths ?? []);
        @endphp
        
        @if(!empty($fontSources))
            @foreach(['AvenirNext-Medium', 'AvenirNext-MediumItalic', 'AvenirNext-DemiBold', 'AvenirNext-DemiBoldItalic'] as $fontKey)
                @if(isset($fontSources[$fontKey]))
                    @php
                        $font = $fontSources[$fontKey];
                        if ($useDataUris) {
                            $fontUrl = $font['data'];
                        } else {
                            // Use file:// protocol with absolute path for DomPDF
                            $fontUrl = 'file://' . $font['path'];
                        }
                    @endphp
                    @font-face {
                        font-family: 'AvenirNext';
                        src: url('{{ $fontUrl }}') format('truetype');
                        font-weight: {{ $font['weight'] }};
                        font-style: {{ $font['style'] }};
                    }
                @endif
            @endforeach
        @endif
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