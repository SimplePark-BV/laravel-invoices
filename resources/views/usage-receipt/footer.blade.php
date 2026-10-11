{{-- usage receipt footer with the disclaimer and the seller details, at the bottom of every page --}}
@php($seller = $usageReceipt->getSeller())

<div class="usage-receipt__footer">
    <div class="usage-receipt__footer-disclaimer">{{ __('invoices::usage-receipt.footer.disclaimer') }}</div>
    <table class="usage-receipt__footer-info">
        <tr>
            <td class="usage-receipt__footer-column">
                <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.address') }}</div>
                <div class="usage-receipt__footer-value">
                    {{ $seller->getName() }}
                    @if($seller->hasAddress())
                        <br>@include('invoices::partials.address', ['party' => $seller])
                    @endif
                </div>
            </td>
            <td class="usage-receipt__footer-column">
                @if($seller->getRegistrationNumber() !== null)
                    <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.registration_number') }}</div>
                    <div class="usage-receipt__footer-value">{{ $seller->getRegistrationNumber() }}</div>
                @endif
                @if($seller->getTaxId() !== null)
                    <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.tax_id') }}</div>
                    <div class="usage-receipt__footer-value">{{ $seller->getTaxId() }}</div>
                @endif
            </td>
            <td class="usage-receipt__footer-column">
                @if($seller->getWebsite() !== null)
                    <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.website') }}</div>
                    <div class="usage-receipt__footer-value">{{ $seller->getWebsite() }}</div>
                @endif
                @if($seller->getEmail() !== null)
                    <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.email') }}</div>
                    <div class="usage-receipt__footer-value">{{ $seller->getEmail() }}</div>
                @endif
                @if($seller->getPhone() !== null)
                    <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.phone') }}</div>
                    <div class="usage-receipt__footer-value">{{ $seller->getPhone() }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>
