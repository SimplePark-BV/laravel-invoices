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
            <div class="invoice__seller-name">{{ $invoice->seller->getName() }}</div>
            <div class="invoice__seller-details">
                <div class="invoice__seller-detail">
                    {{ $invoice->seller->getAddress() }}<br>
                    {{ $invoice->seller->getPostalCode() }} {{ $invoice->seller->getCity() }}
                </div>
                <div class="invoice__seller-detail">
                    {{ $invoice->seller->getEmail() }}
                </div>
                <div class="invoice__seller-detail">
                    @if($invoice->seller->getRegistrationNumber())
                        {{ __('invoices::invoice.registration_number') }} {{ $invoice->seller->getRegistrationNumber() }}<br>
                    @endif
                    @if($invoice->seller->getTaxId())
                        {{ __('invoices::invoice.tax_id') }} {{ $invoice->seller->getTaxId() }}<br>
                    @endif
                    @if($invoice->seller->getBankAccount())
                        {{ __('invoices::invoice.bank') }} {{ $invoice->seller->getBankAccount() }}
                    @endif
                </div>
            </div>
        </td>
    </tr>
</table>
