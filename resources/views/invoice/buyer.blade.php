{{-- buyer information section --}}
<div class="invoice__buyer">
    <div class="invoice__buyer-name">{{ $invoice->buyer->getName() }}</div>
    <div class="invoice__buyer-details">
        <div class="invoice__buyer-detail">
            {{ $invoice->buyer->getAddress() }}<br>
            {{ $invoice->buyer->getPostalCode() }} {{ $invoice->buyer->getCity() }}
        </div>
        <div class="invoice__buyer-detail">
            {{ $invoice->buyer->getEmail() }}<br>
            {{ $invoice->buyer->getPhone() }}
        </div>
    </div>
</div>
