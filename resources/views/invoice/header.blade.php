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
            <div class="invoice__seller-name">{{ $invoice->getSeller()->getName() }}</div>
            <div class="invoice__seller-details">
                <div class="invoice__seller-detail">
                    @if($invoice->getSeller()->getAddress())
                        {{ $invoice->getSeller()->getAddress() }}<br>
                    @endif
                    @if($invoice->getSeller()->getPostalCode() || $invoice->getSeller()->getCity())
                        {{ $invoice->getSeller()->getPostalCode() }} {{ $invoice->getSeller()->getCity() }}
                    @endif
                </div>
                <div class="invoice__seller-detail">
                    @if($invoice->getSeller()->getEmail())
                        {{ $invoice->getSeller()->getEmail() }}
                    @endif
                </div>
                <div class="invoice__seller-detail">
                    @if($invoice->getSeller()->getRegistrationNumber())
                        {{ __('invoices::invoice.registration_number') }} {{ $invoice->getSeller()->getRegistrationNumber() }}<br>
                    @endif
                    @if($invoice->getSeller()->getTaxId())
                        {{ __('invoices::invoice.tax_id') }} {{ $invoice->getSeller()->getTaxId() }}<br>
                    @endif
                    @if($invoice->getSeller()->getBankAccount())
                        {{ __('invoices::invoice.bank') }} {{ $invoice->getSeller()->getBankAccount() }}
                    @endif
                </div>
            </div>
        </td>
    </tr>
</table>
