{{-- the logo, left out when the document has none --}}
@if($logo !== null)
    <img class="document__logo" src="{{ $logo }}" alt="{{ $document->getSeller()->getName() }}">
@endif
