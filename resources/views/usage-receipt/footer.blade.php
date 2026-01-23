{{-- usage receipt footer --}}
@php($seller = $usageReceipt->getSeller())

<div class="usage-receipt__footer">
    <div class="usage-receipt__footer-disclaimer">
        <ul>
            <li>{{ __('invoices::usage-receipt.footer.disclaimer') }}</li>
        </ul>
    </div>
    <div class="usage-receipt__footer-info">
        <div class="usage-receipt__footer-column">
            <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.address') }}</div>
            <div class="usage-receipt__footer-value">
                {{ $seller->getName() }}<br>
                @if($seller->getAddress())
                    {{ $seller->getAddress() }}<br>
                @endif
                @if($seller->getPostalCode() || $seller->getCity())
                    {{ $seller->getPostalCode() }} {{ $seller->getCity() }}
                @endif
            </div>
    </div>
        <div class="usage-receipt__footer-column">
            @if($seller->getRegistrationNumber())
                <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.registration_number') }}</div>
                <div class="usage-receipt__footer-value">{{ $seller->getRegistrationNumber() }}</div>
            @endif
            @if($seller->getTaxId())
                <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.tax_id') }}</div>
                <div class="usage-receipt__footer-value">{{ $seller->getTaxId() }}</div>
            @endif
        </div>
        <div class="usage-receipt__footer-column">
            @if($seller->getWebsite())
                <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.website') }}</div>
                <div class="usage-receipt__footer-value">{{ $seller->getWebsite() }}</div>
            @endif
            @if($seller->getEmail())
                <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.email') }}</div>
                <div class="usage-receipt__footer-value">{{ $seller->getEmail() }}</div>
            @endif
        </div>
    </div>
</div>
