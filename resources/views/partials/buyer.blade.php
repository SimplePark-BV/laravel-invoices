{{-- the buyer: name, address and contact details --}}
@php($buyer = $document->getBuyer())

<div class="document__buyer">
    @if($buyer->getName() !== null)
        <div class="document__buyer-name document__text">{{ $buyer->getName() }}</div>
    @endif
    @if($buyer->hasAddress())
        <div class="document__buyer-detail document__text">@include('invoices::partials.address', ['party' => $buyer, 'withCountry' => $document->showsBuyerCountry()])</div>
    @endif
    @if($buyer->getEmail() !== null || $buyer->getPhone() !== null)
        <div class="document__buyer-detail document__text">
            @include('invoices::partials.lines', ['lines' => [$buyer->getEmail(), $buyer->getPhone()]])
        </div>
    @endif
</div>
