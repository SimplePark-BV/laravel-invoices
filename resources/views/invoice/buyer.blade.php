{{-- buyer information section --}}
<div class="invoice__buyer">
    <div class="invoice__buyer-name">{{ $invoice->buyer->getName() }}</div>
    <div class="invoice__buyer-details">
        @if($invoice->buyer->hasAddress())
            <div class="invoice__buyer-detail">
                @if($invoice->buyer->getAddress())
                    {{ $invoice->buyer->getAddress() }}<br>
                @endif
                @if($invoice->buyer->getPostalCode() || $invoice->buyer->getCity())
                    {{ $invoice->buyer->getPostalCode() }} {{ $invoice->buyer->getCity() }}
                @endif
            </div>
        @endif
        <div class="invoice__buyer-detail">
            @if($invoice->buyer->getEmail())
                {{ $invoice->buyer->getEmail() }}<br>
            @endif
            @if($invoice->buyer->getPhone())
                {{ $invoice->buyer->getPhone() }}
            @endif
        </div>
    </div>
</div>
