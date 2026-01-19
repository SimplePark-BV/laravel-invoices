{{-- usage receipt footer --}}
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
                {{ $usageReceipt->getSeller()->getName() }}<br>
                @if($usageReceipt->getSeller()->getAddress())
                    {{ $usageReceipt->getSeller()->getAddress() }}<br>
                @endif
                @if($usageReceipt->getSeller()->getPostalCode() || $usageReceipt->getSeller()->getCity())
                    {{ $usageReceipt->getSeller()->getPostalCode() }} {{ $usageReceipt->getSeller()->getCity() }}
                @endif
            </div>
        </div>
        <div class="usage-receipt__footer-column">
            @if($usageReceipt->getSeller()->getRegistrationNumber())
            <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.registration_number') }}</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->getSeller()->getRegistrationNumber() }}</div>
            @endif
            @if($usageReceipt->getSeller()->getTaxId())
            <div class="usage-receipt__footer-label usage-receipt__footer-label--spaced">{{ __('invoices::usage-receipt.footer.tax_id') }}</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->getSeller()->getTaxId() }}</div>
            @endif
        </div>
        <div class="usage-receipt__footer-column">
            @if($usageReceipt->getSeller()->getWebsite())
            <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.website') }}</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->getSeller()->getWebsite() }}</div>
            @endif
            @if($usageReceipt->getSeller()->getEmail())
            <div class="usage-receipt__footer-label usage-receipt__footer-label--spaced">{{ __('invoices::usage-receipt.footer.email') }}</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->getSeller()->getEmail() }}</div>
            @endif
        </div>
    </div>
</div>
