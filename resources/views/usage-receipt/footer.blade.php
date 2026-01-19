{{-- usage receipt footer --}}
<div class="usage-receipt__footer">
    <div class="usage-receipt__footer-disclaimer">
        <ul>
            <li>LET OP! Dit is geen betaalverzoek, alleen een parkeerbevestiging</li>
        </ul>
    </div>
    <div class="usage-receipt__footer-info">
        <div class="usage-receipt__footer-column">
            <div class="usage-receipt__footer-label">Adres</div>
            <div class="usage-receipt__footer-value">
                <span class="usage-receipt__footer-company">{{ $usageReceipt->seller->getName() }}</span><br>
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
            <div class="usage-receipt__footer-label">KvK nummer</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->seller->getRegistrationNumber() }}</div>
            @endif
            @if($usageReceipt->seller->getTaxId())
            <div class="usage-receipt__footer-label usage-receipt__footer-label--spaced">Btw nummer</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->seller->getTaxId() }}</div>
            @endif
        </div>
        <div class="usage-receipt__footer-column">
            @if($usageReceipt->seller->getWebsite())
            <div class="usage-receipt__footer-label">Website</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->seller->getWebsite() }}</div>
            @endif
            @if($usageReceipt->seller->getEmail())
            <div class="usage-receipt__footer-label usage-receipt__footer-label--spaced">E-mail</div>
            <div class="usage-receipt__footer-value">{{ $usageReceipt->seller->getEmail() }}</div>
            @endif
        </div>
    </div>
</div>
