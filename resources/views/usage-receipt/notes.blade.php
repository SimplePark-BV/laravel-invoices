{{-- notes section (optional - only shown when note is present) --}}
@if($usageReceipt->note)
<div class="usage-receipt__notes">
    <div class="usage-receipt__notes-label">Notitie</div>
    <div class="usage-receipt__notes-text">{{ $usageReceipt->getNote() }}</div>
</div>
@endif