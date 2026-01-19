{{-- buyer information section --}}
@php($buyer = $invoice->getBuyer())

<div class="invoice__buyer">
    <div class="invoice__buyer-name">{{ $buyer->getName() }}</div>
    <div class="invoice__buyer-details">
        @if($buyer->hasAddress())
            <div class="invoice__buyer-detail">
                @if($buyer->getAddress())
                    {{ $buyer->getAddress() }}<br>
                @endif
                @if($buyer->getPostalCode() || $buyer->getCity())
                    {{ $buyer->getPostalCode() }} {{ $buyer->getCity() }}
                @endif
            </div>
        @endif
        <div class="invoice__buyer-detail">
            @if($buyer->getEmail())
                {{ $buyer->getEmail() }}<br>
            @endif
            @if($buyer->getPhone())
                {{ $buyer->getPhone() }}
            @endif
        </div>
    </div>
</div>
