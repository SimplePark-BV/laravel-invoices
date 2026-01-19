{{-- invoice header with logo and seller info --}}
@php($seller = $invoice->getSeller())

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
            <div class="invoice__seller-name">{{ $seller->getName() }}</div>
            <div class="invoice__seller-details">
                <div class="invoice__seller-detail">
                    @if($seller->getAddress())
                        {{ $seller->getAddress() }}<br>
                    @endif
                    @if($seller->getPostalCode() || $seller->getCity())
                        {{ $seller->getPostalCode() }} {{ $seller->getCity() }}
                    @endif
                </div>
                @if($seller->getEmail())
                    <div class="invoice__seller-detail">
                        {{ $seller->getEmail() }}
                    </div>
                @endif
                @if($seller->getRegistrationNumber() || $seller->getTaxId() || $seller->getBankAccount())
                    <div class="invoice__seller-detail">
                    @if($seller->getRegistrationNumber())
                        {{ __('invoices::invoice.registration_number') }} {{ $seller->getRegistrationNumber() }}<br>
                    @endif
                    @if($seller->getTaxId())
                        {{ __('invoices::invoice.tax_id') }} {{ $seller->getTaxId() }}<br>
                    @endif
                    @if($seller->getBankAccount())
                        {{ __('invoices::invoice.bank') }} {{ $seller->getBankAccount() }}
                    @endif
                    </div>
                @endif
            </div>
        </td>
    </tr>
</table>
