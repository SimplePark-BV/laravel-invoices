{{-- document and user IDs section (optional - only shown when at least one id is present) --}}
@php
    $receiptIds = array_filter([
        'document_id' => $usageReceipt->getDocumentId(),
        'user_id' => $usageReceipt->getUserId(),
    ], fn ($value) => $value !== null && $value !== '');
@endphp
@if(count($receiptIds) > 0)
<div class="usage-receipt__ids">
    @foreach($receiptIds as $labelKey => $value)
    <div class="usage-receipt__id-column">
        <div class="usage-receipt__id-label">{{ __('invoices::usage-receipt.'.$labelKey) }}</div>
        <div class="usage-receipt__id-value">{{ $value }}</div>
    </div>
    @endforeach
    {{-- pad to three columns to keep the column widths consistent --}}
    @for($i = count($receiptIds); $i < 3; $i++)
    <div class="usage-receipt__id-column"></div>
    @endfor
</div>
@endif
