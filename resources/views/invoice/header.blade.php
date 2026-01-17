{{-- invoice header with logo and seller info --}}
<table class="invoice__header">
    <tr>
        <td class="invoice__header-cell">
            @if($invoice->getLogoDataUri())
                <img class="invoice__logo" src="{{ $invoice->getLogoDataUri() }}" alt="Logo">
            @else
                <div class="invoice__logo-placeholder"></div>
            @endif
        </td>

        <td class="invoice__header-cell invoice__seller">
            <div class="invoice__seller-name">{{ $invoice->seller->name }}</div>
            <div class="invoice__seller-details">
                <div class="invoice__seller-detail">
                    {{ $invoice->seller->address }}<br>
                    {{ $invoice->seller->postalCode }} {{ $invoice->seller->city }}
                </div>
                <div class="invoice__seller-detail">
                    {{ $invoice->seller->email }}
                </div>
                <div class="invoice__seller-detail">
                    @if($invoice->seller->registrationNumber)
                        {{ __('invoices::invoice.registration_number') }} {{ $invoice->seller->registrationNumber }}<br>
                    @endif
                    @if($invoice->seller->taxId)
                        {{ __('invoices::invoice.tax_id') }} {{ $invoice->seller->taxId }}<br>
                    @endif
                    @if($invoice->seller->bankAccount)
                        {{ __('invoices::invoice.bank') }} {{ $invoice->seller->bankAccount }}
                    @endif
                </div>
            </div>
        </td>
    </tr>
</table>
