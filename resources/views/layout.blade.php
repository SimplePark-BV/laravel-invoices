{{-- the html document every pdf shares: fonts, stylesheets and the body --}}
<!DOCTYPE html>
<html lang="{{ $document->getLanguage() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->getTitle() }}</title>
    <style>
{!! $fontFaces !!}
body { font-family: '{{ $fontFamily }}', sans-serif; }
{!! $styles !!}
    </style>
</head>
<body class="@yield('body-class')">
@yield('content')
</body>
</html>
