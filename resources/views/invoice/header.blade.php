{{-- invoice header with logo and seller info --}}
@php($seller = $invoice->getSeller())

<table class="invoice__header">
    <tr>
        <td class="invoice__header-cell">
            @include('invoices::partials.logo')
        </td>

        <td class="invoice__header-cell invoice__seller">
            <div class="invoice__seller-name">{{ $seller->getName() }}</div>
            @if($seller->hasAddress())
                <div class="invoice__seller-detail">@include('invoices::partials.address', ['party' => $seller])</div>
            @endif
            @if($seller->getEmail() !== null || $seller->getPhone() !== null)
                <div class="invoice__seller-detail">
                    @include('invoices::partials.lines', ['lines' => [$seller->getEmail(), $seller->getPhone()]])
                </div>
            @endif
            @if($seller->getRegistrationNumber() !== null || $seller->getTaxId() !== null || $seller->getBankAccount() !== null)
                <div class="invoice__seller-detail">
                    @if($seller->getRegistrationNumber() !== null)
                        {{ __('invoices::invoice.registration_number') }} {{ $seller->getRegistrationNumber() }}<br>
                    @endif
                    @if($seller->getTaxId() !== null)
                        {{ __('invoices::invoice.tax_id') }} {{ $seller->getTaxId() }}<br>
                    @endif
                    @if($seller->getBankAccount() !== null)
                        {{ __('invoices::invoice.bank') }} {{ $seller->getBankAccount() }}
                    @endif
                </div>
            @endif
        </td>
    </tr>
</table>
