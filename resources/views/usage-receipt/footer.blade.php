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
                {{ $usageReceipt->seller->getName() }}<br>
                @if($usageReceipt->seller->getAddress())
                    {{ $usageReceipt->seller->getAddress() }}<br>
                @endif
                @if($usageReceipt->seller->getPostalCode() || $usageReceipt->seller->getCity())
                    {{ $usageReceipt->seller->getPostalCode() }} {{ $usageReceipt->seller->getCity() }}
                @endif
            </div>
        </div>
        <div class="usage-receipt__footer-column">
            @if($usageReceipt->seller->getRegistrationNumber())
            <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.registration_number') }}</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->seller->getRegistrationNumber() }}</div>
            @endif
            @if($usageReceipt->seller->getTaxId())
            <div class="usage-receipt__footer-label usage-receipt__footer-label--spaced">{{ __('invoices::usage-receipt.footer.tax_id') }}</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->seller->getTaxId() }}</div>
            @endif
        </div>
        <div class="usage-receipt__footer-column">
            @if($usageReceipt->seller->getWebsite())
            <div class="usage-receipt__footer-label">{{ __('invoices::usage-receipt.footer.website') }}</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->seller->getWebsite() }}</div>
            @endif
            @if($usageReceipt->seller->getEmail())
            <div class="usage-receipt__footer-label usage-receipt__footer-label--spaced">{{ __('invoices::usage-receipt.footer.email') }}</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->seller->getEmail() }}</div>
            @endif
        </div>
    </div>
</div>
