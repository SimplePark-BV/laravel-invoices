{{-- invoice header with logo and seller info --}}
<table class="invoice__header">
    <tr>
        <td class="invoice__header-cell">
            @if($invoice->getLogoDataUri())
                <img class="invoice__logo" src="{{ $invoice->getLogoDataUri() }}" alt="Logo">
            @endif
        </td>

        <td class="invoice__header-cell invoice__seller">
            <div class="invoice__seller-name">{{ $invoice->seller->name }}</div>
            <div class="invoice__seller-details">
                <div class="invoice__seller-detail">
                    {{ $invoice->seller->address }}<br>
                    {{ $invoice->seller->postal_code }} {{ $invoice->seller->city }}
                </div>
                <div class="invoice__seller-detail">
                    {{ $invoice->seller->email }}
                </div>
                <div class="invoice__seller-detail">
                    KVK: {{ $invoice->seller->kvk ?? 'Unknown' }}<br>
                    Btw: {{ $invoice->seller->btw ?? 'Unknown' }}<br>
                    Bank: {{ $invoice->seller->iban ?? 'Unknown' }}
                </div>
            </div>
        </td>
    </tr>
</table>
