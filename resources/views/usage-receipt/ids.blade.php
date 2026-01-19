{{-- document and user IDs section --}}
<div class="usage-receipt__ids">
    <div class="usage-receipt__id-column">
        <div class="usage-receipt__id-label">{{ __('invoices::usage-receipt.document_id') }}</div>
        <div class="usage-receipt__id-value">{{ $usageReceipt->getDocumentId() }}</div>
    </div>
    <div class="usage-receipt__id-column">
        <div class="usage-receipt__id-label">{{ __('invoices::usage-receipt.user_id') }}</div>
        <div class="usage-receipt__id-value">{{ $usageReceipt->getUserId() }}</div>
    </div>
    <div class="usage-receipt__id-column"></div>
</div>
