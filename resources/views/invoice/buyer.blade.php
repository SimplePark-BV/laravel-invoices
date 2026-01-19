{{-- buyer information section --}}
<div class="invoice__buyer">
    <div class="invoice__buyer-name">{{ $invoice->getBuyer()->getName() }}</div>
    <div class="invoice__buyer-details">
        @if($invoice->getBuyer()->hasAddress())
            <div class="invoice__buyer-detail">
                @if($invoice->getBuyer()->getAddress())
                    {{ $invoice->getBuyer()->getAddress() }}<br>
                @endif
                @if($invoice->getBuyer()->getPostalCode() || $invoice->getBuyer()->getCity())
                    {{ $invoice->getBuyer()->getPostalCode() }} {{ $invoice->getBuyer()->getCity() }}
                @endif
            </div>
        @endif
        <div class="invoice__buyer-detail">
            @if($invoice->getBuyer()->getEmail())
                {{ $invoice->getBuyer()->getEmail() }}<br>
            @endif
            @if($invoice->getBuyer()->getPhone())
                {{ $invoice->getBuyer()->getPhone() }}
            @endif
        </div>
    </div>
</div>
