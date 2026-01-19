{{-- usage receipt header with logo --}}
<div class="usage-receipt__header">
    @if($usageReceipt->getLogoDataUri())
        <img class="usage-receipt__logo" src="{{ $usageReceipt->getLogoDataUri() }}" alt="Logo">
    @else
        <div class="usage-receipt__logo-placeholder"></div>
    @endif
</div>
