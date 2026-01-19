{{-- buyer information section --}}
<div class="usage-receipt__buyer">
    <div class="usage-receipt__buyer-name">{{ $usageReceipt->getBuyer()->getName() }}</div>
    <div class="usage-receipt__buyer-details">
        @if($usageReceipt->getBuyer()->hasAddress())
            <div class="usage-receipt__buyer-detail">
                @if($usageReceipt->getBuyer()->getAddress())
                    {{ $usageReceipt->getBuyer()->getAddress() }}<br>
                @endif
                @if($usageReceipt->getBuyer()->getPostalCode() || $usageReceipt->getBuyer()->getCity())
                    {{ $usageReceipt->getBuyer()->getPostalCode() }} {{ $usageReceipt->getBuyer()->getCity() }}
                @endif
            </div>
        @endif
        <div class="usage-receipt__buyer-detail">
            @if($usageReceipt->getBuyer()->getEmail())
                {{ $usageReceipt->getBuyer()->getEmail() }}<br>
            @endif
            @if($usageReceipt->getBuyer()->getPhone())
                {{ $usageReceipt->getBuyer()->getPhone() }}
            @endif
        </div>
    </div>
</div>
