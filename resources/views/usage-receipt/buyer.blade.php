{{-- buyer information section --}}
@php($buyer = $usageReceipt->getBuyer())

<div class="usage-receipt__buyer">
    <div class="usage-receipt__buyer-name">{{ $buyer->getName() }}</div>
    <div class="usage-receipt__buyer-details">
        @if($buyer->hasAddress())
            <div class="usage-receipt__buyer-detail">
                @if($buyer->getAddress())
                    {{ $buyer->getAddress() }}<br>
                @endif
                @if($buyer->getPostalCode() || $buyer->getCity())
                    {{ $buyer->getPostalCode() }} {{ $buyer->getCity() }}
                @endif
            </div>
        @endif
        <div class="usage-receipt__buyer-detail">
            @if($buyer->getEmail())
                {{ $buyer->getEmail() }}<br>
            @endif
            @if($buyer->getPhone())
                {{ $buyer->getPhone() }}
            @endif
        </div>
    </div>
</div>
