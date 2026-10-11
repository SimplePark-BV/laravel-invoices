{{-- the note, only shown when the receipt has one; line breaks are kept --}}
@if($usageReceipt->getNote() !== null)
    <div class="usage-receipt__notes">
        <div class="usage-receipt__notes-label">{{ __('invoices::usage-receipt.note') }}</div>
        <div class="usage-receipt__notes-text document__text">{!! nl2br(e($usageReceipt->getNote())) !!}</div>
    </div>
@endif
