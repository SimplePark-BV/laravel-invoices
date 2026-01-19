{{-- notes section (optional - only shown when note is present) --}}
@if($usageReceipt->note)
<div class="usage-receipt__notes">
    <div class="usage-receipt__notes-label">{{ __('invoices::usage-receipt.note') }}</div>
    <div class="usage-receipt__notes-text">{{ $usageReceipt->getNote() }}</div>
</div>
@endif