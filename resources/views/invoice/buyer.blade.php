{{-- buyer information section --}}
<div class="invoice__buyer">
    <div class="invoice__buyer-name">{{ $invoice->buyer->name }}</div>
    <div class="invoice__buyer-details">
        <div class="invoice__buyer-detail">
            {{ $invoice->buyer->address }}<br>
            {{ $invoice->buyer->postal_code }} {{ $invoice->buyer->city }}
        </div>
        <div class="invoice__buyer-detail">
            {{ $invoice->buyer->email }}<br>
            {{ $invoice->buyer->phone }}
        </div>
    </div>
</div>
