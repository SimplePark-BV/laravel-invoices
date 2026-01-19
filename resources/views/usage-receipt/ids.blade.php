{{-- parking and user IDs section --}}
<div class="usage-receipt__ids">
    <div class="usage-receipt__id-column">
        <div class="usage-receipt__id-label">Parkeer ID</div>
        <div class="usage-receipt__id-value">{{ $usageReceipt->getDocumentId() }}</div>
    </div>
    <div class="usage-receipt__id-column">
        <div class="usage-receipt__id-label">Parkeergebruiker ID</div>
        <div class="usage-receipt__id-value">{{ $usageReceipt->getUserId() }}</div>
    </div>
    <div class="usage-receipt__id-column"></div>
</div>
