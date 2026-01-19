{{-- buyer information section --}}
<div class="usage-receipt__buyer">
    <div class="usage-receipt__buyer-name">{{ $usageReceipt->buyer->getName() }}</div>
    <div class="usage-receipt__buyer-details">
        @if($usageReceipt->buyer->hasAddress())
            <div class="usage-receipt__buyer-detail">
                @if($usageReceipt->buyer->getAddress())
                    {{ $usageReceipt->buyer->getAddress() }}<br>
                @endif
                @if($usageReceipt->buyer->getPostalCode() || $usageReceipt->buyer->getCity())
                    {{ $usageReceipt->buyer->getPostalCode() }} {{ $usageReceipt->buyer->getCity() }}
                @endif
            </div>
        @endif
        <div class="usage-receipt__buyer-detail">
            @if($usageReceipt->buyer->getEmail())
                {{ $usageReceipt->buyer->getEmail() }}<br>
            @endif
            @if($usageReceipt->buyer->getPhone())
                {{ $usageReceipt->buyer->getPhone() }}
            @endif
        </div>
    </div>
</div>
